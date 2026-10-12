<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Transforma os indicadores gravados em dados de gráfico, números de
 * destaque e textos interpretativos.
 *
 * Não acessa o banco: recebe as linhas de report_indicators agrupadas por
 * indicador e devolve estruturas prontas para exibir. O resultado é o mesmo
 * que fica congelado na versão publicada, por isso não depende de nada além
 * dos dados recebidos.
 *
 * Estrutura de um gráfico montado:
 *   tipo, modo, titulo, unidade, formato
 *   labels    rótulos do eixo X (ou das fatias)
 *   datasets  [['rotulo', 'abrangencia', 'valores' => [...], 'papel' => 'principal'|'percentual']]
 *   periodo   período exibido ("2022" ou "2017 a 2025")
 *   fontes    fontes distintas das linhas usadas
 *   vazio     true quando não há dado para desenhar
 */
class Relatorio_graficos {

    const ABRANGENCIAS = ['municipio', 'estado', 'brasil'];
    const RANKING_LIMITE = 10;

    /** @var array catálogo de indicadores */
    private $indicadores = [];

    public function __construct($params = []) {
        if (isset($params['indicadores'])) {
            $this->indicadores = $params['indicadores'];
        }
    }

    public function definir_catalogo($indicadores) {
        $this->indicadores = $indicadores;
    }

    // ------------------------------------------------------------
    // Gráficos
    // ------------------------------------------------------------

    /**
     * @param array $def      definição do gráfico no catálogo
     * @param array $grupos   indicador => linhas
     * @param array $contexto ['municipio' => nome, 'uf' => 'PA']
     */
    public function montar($def, $grupos, $contexto) {
        $chave = $def['indicador'] ?? null;
        $ind = $chave ? ($this->indicadores[$chave] ?? []) : [];

        $base = [
            'tipo'     => $def['tipo'],
            'modo'     => $def['modo'],
            'titulo'   => $def['titulo'],
            'indicador_rotulo' => $ind['rotulo'] ?? $def['titulo'],
            'unidade'  => $ind['unidade'] ?? '',
            'formato'  => $ind['formato'] ?? 'decimal2',
            'labels'   => [],
            'datasets' => [],
            'periodo'  => '',
            'fontes'   => [],
            'vazio'    => true,
        ];

        switch ($def['modo']) {
            case 'serie':       $dados = $this->modo_serie($chave, $grupos, $contexto); break;
            case 'categorias':  $dados = $this->modo_categorias($chave, $grupos, $contexto); break;
            case 'ranking':     $dados = $this->modo_ranking($chave, $grupos, $contexto); break;
            case 'composicao':  $dados = $this->modo_composicao($chave, $grupos, $contexto); break;
            case 'piramide':    $dados = $this->modo_piramide($chave, $grupos, $contexto); break;
            case 'combinado':   $dados = $this->modo_combinado($chave, $grupos, $contexto); break;
            case 'indicadores':
                $dados = $this->modo_indicadores($def['indicadores'] ?? [], $grupos, $contexto);
                $primeiro = $this->indicadores[$def['indicadores'][0] ?? ''] ?? [];
                $base['unidade'] = $primeiro['unidade'] ?? '';
                $base['formato'] = $primeiro['formato'] ?? 'decimal2';
                break;
            default:            $dados = [];
        }

        $grafico = array_merge($base, $dados);

        // Totais (população, PIB, internações) não se comparam entre
        // abrangências: no mesmo eixo, o município vira uma linha colada no
        // zero ao lado dos milhões do estado e do país. Ficam só os do município.
        if (in_array($grafico['modo'], ['serie', 'categorias', 'indicadores'], true) && !$this->comparavel($grafico)) {
            $mun = array_values(array_filter($grafico['datasets'], function ($d) { return $d['abrangencia'] === 'municipio'; }));
            if (!empty($mun)) {
                $grafico['datasets'] = $mun;
            }
        }

        $grafico['vazio'] = empty($grafico['labels']) || empty($grafico['datasets']);

        return $grafico;
    }

    /** Eixo X = períodos; uma série por abrangência. */
    private function modo_serie($chave, $grupos, $ctx) {
        $linhas = $this->linhas($grupos, $chave, false);
        $periodos = $this->periodos($linhas);
        if (empty($periodos)) {
            return [];
        }

        $datasets = [];
        foreach (self::ABRANGENCIAS as $abr) {
            $porPeriodo = [];
            foreach ($linhas as $l) {
                if ($l['abrangencia'] === $abr && $l['valor'] !== null) {
                    $porPeriodo[$l['periodo']] = (float) $l['valor'];
                }
            }
            if (empty($porPeriodo)) {
                continue;
            }
            $datasets[] = [
                'rotulo'      => $this->rotulo_abrangencia($abr, $ctx),
                'abrangencia' => $abr,
                'valores'     => array_map(function ($p) use ($porPeriodo) {
                    return $porPeriodo[$p] ?? null;
                }, $periodos),
                'papel'       => 'principal',
            ];
        }

        return [
            'labels'   => $periodos,
            'datasets' => $datasets,
            'periodo'  => count($periodos) > 1 ? reset($periodos) . ' a ' . end($periodos) : (string) reset($periodos),
            'fontes'   => $this->fontes($linhas),
        ];
    }

    /** Eixo X = categorias do período mais recente; uma série por abrangência. */
    private function modo_categorias($chave, $grupos, $ctx) {
        $linhas = $this->linhas($grupos, $chave, true);
        $periodo = $this->ultimo_periodo($linhas);
        $linhas = array_values(array_filter($linhas, function ($l) use ($periodo) { return $l['periodo'] === $periodo; }));
        $categorias = $this->categorias($linhas);
        if (empty($categorias)) {
            return [];
        }

        $datasets = [];
        foreach (self::ABRANGENCIAS as $abr) {
            $porCat = [];
            foreach ($linhas as $l) {
                if ($l['abrangencia'] === $abr && $l['valor'] !== null) {
                    $porCat[$l['categoria']] = (float) $l['valor'];
                }
            }
            if (empty($porCat)) {
                continue;
            }
            $datasets[] = [
                'rotulo'      => $this->rotulo_abrangencia($abr, $ctx),
                'abrangencia' => $abr,
                'valores'     => array_map(function ($c) use ($porCat) { return $porCat[$c] ?? null; }, $categorias),
                'papel'       => 'principal',
            ];
        }

        return [
            'labels'   => $categorias,
            'datasets' => $datasets,
            'periodo'  => $periodo,
            'fontes'   => $this->fontes($linhas),
        ];
    }

    /** Maiores categorias do município, da maior para a menor. */
    private function modo_ranking($chave, $grupos, $ctx) {
        list($porCat, $periodo, $linhas) = $this->categorias_municipio($chave, $grupos);
        if (empty($porCat)) {
            return [];
        }
        arsort($porCat);
        $porCat = array_slice($porCat, 0, self::RANKING_LIMITE, true);

        return [
            'labels'   => array_map('strval', array_keys($porCat)),
            'datasets' => [[
                'rotulo'      => $this->rotulo_abrangencia('municipio', $ctx),
                'abrangencia' => 'municipio',
                'valores'     => array_values($porCat),
                'papel'       => 'principal',
            ]],
            'periodo'  => $periodo,
            'fontes'   => $this->fontes($linhas),
        ];
    }

    /** Participação percentual de cada categoria do município. */
    private function modo_composicao($chave, $grupos, $ctx) {
        list($porCat, $periodo, $linhas) = $this->categorias_municipio($chave, $grupos);
        $total = array_sum($porCat);
        if (empty($porCat) || $total <= 0) {
            return [];
        }
        arsort($porCat);

        return [
            'labels'    => array_map('strval', array_keys($porCat)),
            'datasets'  => [[
                'rotulo'      => $this->rotulo_abrangencia('municipio', $ctx),
                'abrangencia' => 'municipio',
                'valores'     => array_map(function ($v) use ($total) { return round($v / $total * 100, 1); }, array_values($porCat)),
                'absolutos'   => array_values($porCat),
                'papel'       => 'principal',
            ]],
            'formato'   => 'percentual',
            'periodo'   => $periodo,
            'fontes'    => $this->fontes($linhas),
        ];
    }

    /**
     * Pirâmide etária do município, em % da população.
     * Categorias no formato "faixa|Homens" e "faixa|Mulheres".
     */
    private function modo_piramide($chave, $grupos, $ctx) {
        list($porCat, $periodo, $linhas) = $this->categorias_municipio($chave, $grupos);
        $total = array_sum($porCat);
        if (empty($porCat) || $total <= 0) {
            return [];
        }

        $homens = [];
        $mulheres = [];
        foreach ($porCat as $cat => $valor) {
            $partes = explode('|', (string) $cat);
            if (count($partes) !== 2) {
                continue;
            }
            $faixa = trim($partes[0]);
            $sexo = mb_strtolower(trim($partes[1]));
            $pct = round($valor / $total * 100, 1);
            // "hom" cobre Homem e Homens; "mul", Mulher e Mulheres
            if (strpos($sexo, 'hom') === 0 || strpos($sexo, 'masc') === 0) {
                $homens[$faixa] = $pct;
            } elseif (strpos($sexo, 'mul') === 0 || strpos($sexo, 'fem') === 0) {
                $mulheres[$faixa] = $pct;
            }
        }

        $faixas = array_unique(array_merge(array_keys($homens), array_keys($mulheres)));
        if (empty($faixas)) {
            return [];
        }
        // Mais velhos no topo: o gráfico desenha de cima para baixo
        usort($faixas, function ($a, $b) { return $this->idade_inicial($b) <=> $this->idade_inicial($a); });

        return [
            'labels'   => array_values($faixas),
            'datasets' => [
                ['rotulo' => 'Homens', 'abrangencia' => 'homens', 'papel' => 'principal',
                 'valores' => array_map(function ($f) use ($homens) { return isset($homens[$f]) ? -$homens[$f] : null; }, $faixas)],
                ['rotulo' => 'Mulheres', 'abrangencia' => 'mulheres', 'papel' => 'principal',
                 'valores' => array_map(function ($f) use ($mulheres) { return $mulheres[$f] ?? null; }, $faixas)],
            ],
            'formato'  => 'percentual',
            'periodo'  => $periodo,
            'fontes'   => $this->fontes($linhas),
        ];
    }

    /** Categorias do município: quantidade (colunas) e % do total (linha). */
    private function modo_combinado($chave, $grupos, $ctx) {
        list($porCat, $periodo, $linhas) = $this->categorias_municipio($chave, $grupos);
        $total = array_sum($porCat);
        if (empty($porCat) || $total <= 0) {
            return [];
        }
        arsort($porCat);

        return [
            'labels'   => array_map('strval', array_keys($porCat)),
            'datasets' => [
                ['rotulo' => 'Quantidade', 'abrangencia' => 'municipio', 'papel' => 'principal',
                 'valores' => array_values($porCat)],
                ['rotulo' => '% do total', 'abrangencia' => 'percentual', 'papel' => 'percentual',
                 'valores' => array_map(function ($v) use ($total) { return round($v / $total * 100, 1); }, array_values($porCat))],
            ],
            'periodo'  => $periodo,
            'fontes'   => $this->fontes($linhas),
        ];
    }

    /** Eixo X = vários indicadores (período mais recente de cada); uma série por abrangência. */
    private function modo_indicadores($chaves, $grupos, $ctx) {
        $labels = [];
        $porAbr = [];
        $todas = [];
        $periodos = [];

        foreach ($chaves as $chave) {
            $linhas = $this->linhas($grupos, $chave, false);
            if (empty($linhas)) {
                continue;
            }
            $periodo = $this->ultimo_periodo($linhas);
            $periodos[] = $periodo;
            $labels[] = $this->indicadores[$chave]['rotulo'] ?? $chave;
            $i = count($labels) - 1;
            foreach ($linhas as $l) {
                if ($l['periodo'] === $periodo && $l['valor'] !== null) {
                    $porAbr[$l['abrangencia']][$i] = (float) $l['valor'];
                }
            }
            $todas = array_merge($todas, $linhas);
        }

        if (empty($labels)) {
            return [];
        }

        $datasets = [];
        foreach (self::ABRANGENCIAS as $abr) {
            if (empty($porAbr[$abr])) {
                continue;
            }
            $valores = [];
            for ($i = 0; $i < count($labels); $i++) {
                $valores[] = $porAbr[$abr][$i] ?? null;
            }
            $datasets[] = [
                'rotulo'      => $this->rotulo_abrangencia($abr, $ctx),
                'abrangencia' => $abr,
                'valores'     => $valores,
                'papel'       => 'principal',
            ];
        }

        $periodos = array_unique($periodos);

        return [
            'labels'   => $labels,
            'datasets' => $datasets,
            'periodo'  => implode(', ', $periodos),
            'fontes'   => $this->fontes($todas),
        ];
    }

    // ------------------------------------------------------------
    // Textos interpretativos
    // ------------------------------------------------------------

    /**
     * Texto sugerido para o gráfico. É um ponto de partida: a equipe revisa
     * e edita antes de aprovar.
     */
    public function texto_sugerido($g, $contexto) {
        if ($g['vazio']) {
            return '';
        }

        $m = $contexto['municipio'];
        $f = function ($v) use ($g) { return $this->formatar($v, $g['formato'], $g['unidade']); };
        $principal = $g['datasets'][0];

        switch ($g['modo']) {
            case 'serie':
                return $this->texto_serie($g, $m, $f);

            case 'categorias':
                $mun = $this->dataset($g, 'municipio');
                if (!$mun) {
                    return '';
                }
                $i = $this->indice_maior($mun['valores']);
                $txt = "Em {$g['periodo']}, o maior valor de $m foi em {$g['labels'][$i]} ({$f($mun['valores'][$i])}).";
                $est = $this->dataset($g, 'estado');
                if ($est && $est['valores'][$i] !== null) {
                    $comp = $mun['valores'][$i] >= $est['valores'][$i] ? 'acima' : 'abaixo';
                    $txt .= " O resultado ficou $comp da média do estado ({$f($est['valores'][$i])}).";
                }
                return $txt;

            case 'ranking':
                $itens = [];
                foreach (array_slice($g['labels'], 0, 3) as $i => $label) {
                    $itens[] = $label . ' (' . $f($principal['valores'][$i]) . ')';
                }
                return "Em {$g['periodo']}, destacam-se em $m: " . $this->lista($itens) . '.';

            case 'composicao':
                $txt = "Em {$g['periodo']}, {$g['labels'][0]} responde pela maior parcela, com {$this->formatar($principal['valores'][0], 'percentual', '%')} do total";
                if (isset($g['labels'][1])) {
                    $txt .= ", seguido de {$g['labels'][1]} ({$this->formatar($principal['valores'][1], 'percentual', '%')})";
                }
                return $txt . '.';

            case 'piramide':
                return $this->texto_piramide($g, $m);

            case 'combinado':
                $total = array_sum($principal['valores']);
                $pct = $this->formatar($g['datasets'][1]['valores'][0], 'percentual', '%');
                return "{$g['labels'][0]} concentra $pct dos {$this->formatar($total, 'inteiro', '')} registros de $m em {$g['periodo']}.";

            case 'indicadores':
                $mun = $this->dataset($g, 'municipio');
                if (!$mun) {
                    return '';
                }
                $validos = array_filter($mun['valores'], function ($v) { return $v !== null; });
                if (count($validos) < 2) {
                    return '';
                }
                $maior = array_search(max($validos), $mun['valores'], true);
                $menor = array_search(min($validos), $mun['valores'], true);
                return "Entre as dimensões avaliadas, $m tem o melhor resultado em {$g['labels'][$maior]} ({$f($mun['valores'][$maior])})"
                     . " e o mais baixo em {$g['labels'][$menor]} ({$f($mun['valores'][$menor])}).";
        }

        return '';
    }

    private function texto_serie($g, $m, $f) {
        $mun = $this->dataset($g, 'municipio');
        if (!$mun) {
            $outro = $g['datasets'][0];
            $i = $this->indice_ultimo($outro['valores']);
            return $i === null ? '' : "Não há dado municipal; o último valor disponível para {$outro['rotulo']} é {$f($outro['valores'][$i])} ({$g['labels'][$i]}).";
        }

        $primeiro = $this->indice_primeiro($mun['valores']);
        $ultimo = $this->indice_ultimo($mun['valores']);
        $vf = $mun['valores'][$ultimo];

        // A frase começa pelo nome do indicador para dispensar artigo (que
        // dependeria do gênero de cada um)
        $nome = $g['indicador_rotulo'] ?? $g['titulo'];

        if ($primeiro === $ultimo) {
            $txt = "$nome em $m: {$f($vf)} em {$g['labels'][$ultimo]}.";
        } else {
            $vi = $mun['valores'][$primeiro];
            $txt = "$nome em $m passou de {$f($vi)} em {$g['labels'][$primeiro]} para {$f($vf)} em {$g['labels'][$ultimo]}";
            if ($vi != 0) {
                $var = ($vf - $vi) / abs($vi) * 100;
                if (abs($var) < 0.5) {
                    $txt .= ', praticamente estável';
                } else {
                    $txt .= ($var > 0 ? ', aumento de ' : ', redução de ') . $this->formatar(abs($var), 'percentual', '%');
                }
            }
            $txt .= '.';
        }

        // Comparação no mesmo período com estado e Brasil. Só para taxas,
        // índices e valores per capita: comparar totais (população, PIB) de
        // um município com o do país não informa nada.
        $comparacoes = [];
        if ($this->comparavel($g)) {
            foreach (['estado' => 'do estado', 'brasil' => 'do Brasil'] as $abr => $rotulo) {
                $d = $this->dataset($g, $abr);
                if ($d && $d['valores'][$ultimo] !== null) {
                    $comparacoes[] = ($vf >= $d['valores'][$ultimo] ? 'acima ' : 'abaixo ') . $rotulo . ' (' . $f($d['valores'][$ultimo]) . ')';
                }
            }
        }
        if (!empty($comparacoes)) {
            $txt .= " Em {$g['labels'][$ultimo]}, o município ficou " . $this->lista($comparacoes) . '.';
        }

        return $txt;
    }

    private function texto_piramide($g, $m) {
        $totais = [];
        $idosos = 0.0;
        foreach ($g['labels'] as $i => $faixa) {
            $soma = abs((float) ($g['datasets'][0]['valores'][$i] ?? 0)) + (float) ($g['datasets'][1]['valores'][$i] ?? 0);
            $totais[$faixa] = $soma;
            if ($this->idade_inicial($faixa) >= 60) {
                $idosos += $soma;
            }
        }
        arsort($totais);
        $maior = array_key_first($totais);

        return "Em {$g['periodo']}, a faixa etária com mais moradores em $m é a de $maior ("
             . $this->formatar($totais[$maior], 'percentual', '%') . ' da população). Pessoas com 60 anos ou mais somam '
             . $this->formatar($idosos, 'percentual', '%') . '.';
    }

    // ------------------------------------------------------------
    // Números de destaque e informações qualitativas
    // ------------------------------------------------------------

    /**
     * Último valor municipal de cada indicador, para os cartões da seção.
     */
    public function kpis($chaves, $grupos) {
        $out = [];
        foreach ($chaves as $chave) {
            $linhas = $this->linhas($grupos, $chave, false);
            $mun = array_values(array_filter($linhas, function ($l) {
                return $l['abrangencia'] === 'municipio' && $l['valor'] !== null;
            }));
            if (empty($mun)) {
                continue;
            }
            $periodo = $this->ultimo_periodo($mun);
            $linha = null;
            foreach ($mun as $l) {
                if ($l['periodo'] === $periodo) {
                    $linha = $l;
                }
            }
            $ind = $this->indicadores[$chave] ?? [];
            $out[] = [
                'indicador' => $chave,
                'rotulo'    => $ind['rotulo'] ?? $chave,
                'valor'     => $this->formatar($linha['valor'], $ind['formato'] ?? 'decimal2', $ind['unidade'] ?? '', false),
                'unidade'   => in_array($ind['formato'] ?? '', ['percentual', 'moeda'], true) ? '' : ($ind['unidade'] ?? ''),
                'periodo'   => $periodo,
                'fonte'     => $linha['fonte'] ?? ($ind['fonte'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * Informações em texto (histórico, política ambiental...) de um eixo.
     */
    public function qualitativos($eixo, $grupos) {
        $out = [];
        foreach ($this->indicadores as $chave => $ind) {
            if (($ind['eixo'] ?? '') !== $eixo || ($ind['tipo'] ?? 'numero') !== 'texto') {
                continue;
            }
            foreach ($this->linhas($grupos, $chave, null) as $l) {
                if ($l['abrangencia'] === 'municipio' && trim((string) $l['valor_texto']) !== '') {
                    $out[] = [
                        'indicador' => $chave,
                        'rotulo'    => $ind['rotulo'],
                        'texto'     => trim($l['valor_texto']),
                        'fonte'     => $l['fonte'] ?: ($ind['fonte'] ?? ''),
                        'periodo'   => $l['periodo'],
                    ];
                }
            }
        }

        return $out;
    }

    // ------------------------------------------------------------
    // Formatação
    // ------------------------------------------------------------

    public function formatar($valor, $formato, $unidade = '', $com_unidade = true) {
        if ($valor === null || $valor === '') {
            return '-';
        }
        $v = (float) $valor;

        switch ($formato) {
            case 'inteiro':
                $txt = number_format($v, 0, ',', '.');
                break;
            case 'decimal1':
                $txt = number_format($v, 1, ',', '.');
                break;
            case 'percentual':
                return number_format($v, 1, ',', '.') . '%';
            case 'moeda':
                $mil = mb_stripos((string) $unidade, 'mil') !== false;
                return 'R$ ' . number_format($v, $mil ? 0 : 2, ',', '.') . ($mil ? ' mil' : '');
            case 'indice':
                return number_format($v, 3, ',', '.');
            default: // decimal2
                $txt = number_format($v, 2, ',', '.');
        }

        return ($com_unidade && $unidade !== '' && $unidade !== '%') ? $txt . ' ' . $unidade : $txt;
    }

    // ------------------------------------------------------------
    // Internos
    // ------------------------------------------------------------

    /**
     * Linhas de um indicador como arrays.
     *
     * @param bool|null $com_categoria true: só com categoria; false: só sem; null: todas
     */
    private function linhas($grupos, $chave, $com_categoria) {
        $out = [];
        foreach ($grupos[$chave] ?? [] as $row) {
            $l = (array) $row;
            $l['periodo'] = (string) ($l['periodo'] ?? '');
            $l['categoria'] = (string) ($l['categoria'] ?? '');
            $l['valor'] = (isset($l['valor']) && $l['valor'] !== '') ? (float) $l['valor'] : null;
            if ($com_categoria === true && $l['categoria'] === '') {
                continue;
            }
            if ($com_categoria === false && $l['categoria'] !== '') {
                continue;
            }
            $out[] = $l;
        }

        return $out;
    }

    /** Categorias do município no período mais recente: [categoria => valor, periodo, linhas usadas]. */
    private function categorias_municipio($chave, $grupos) {
        $linhas = array_values(array_filter($this->linhas($grupos, $chave, true), function ($l) {
            return $l['abrangencia'] === 'municipio' && $l['valor'] !== null;
        }));
        $periodo = $this->ultimo_periodo($linhas);
        $porCat = [];
        $usadas = [];
        foreach ($linhas as $l) {
            if ($l['periodo'] === $periodo) {
                $porCat[$l['categoria']] = (float) $l['valor'];
                $usadas[] = $l;
            }
        }

        return [$porCat, $periodo, $usadas];
    }

    private function periodos($linhas) {
        $p = array_values(array_unique(array_column($linhas, 'periodo')));
        sort($p, SORT_NATURAL);

        return $p;
    }

    /** Período mais recente, preferindo os que têm dado do município. */
    private function ultimo_periodo($linhas) {
        $mun = array_filter($linhas, function ($l) { return $l['abrangencia'] === 'municipio'; });
        $p = $this->periodos(!empty($mun) ? $mun : $linhas);

        return empty($p) ? '' : end($p);
    }

    private function categorias($linhas) {
        $c = [];
        foreach ($linhas as $l) {
            if (!in_array($l['categoria'], $c, true)) {
                $c[] = $l['categoria'];
            }
        }

        return $c;
    }

    private function fontes($linhas) {
        $f = [];
        foreach ($linhas as $l) {
            $nome = trim((string) ($l['fonte'] ?? ''));
            if ($nome !== '' && !in_array($nome, $f, true)) {
                $f[] = $nome;
            }
        }

        return $f;
    }

    private function rotulo_abrangencia($abr, $ctx) {
        if ($abr === 'municipio') {
            return $ctx['municipio'] ?? 'Município';
        }
        if ($abr === 'estado') {
            return 'Estado' . (!empty($ctx['uf']) ? ' (' . $ctx['uf'] . ')' : '');
        }

        return 'Brasil';
    }

    private function dataset($g, $abrangencia) {
        foreach ($g['datasets'] as $d) {
            if ($d['abrangencia'] === $abrangencia) {
                return $d;
            }
        }

        return null;
    }

    private function idade_inicial($faixa) {
        return preg_match('/\d+/', (string) $faixa, $m) ? (int) $m[0] : -1;
    }

    private function indice_maior($valores) {
        $max = null;
        $idx = 0;
        foreach ($valores as $i => $v) {
            if ($v !== null && ($max === null || $v > $max)) {
                $max = $v;
                $idx = $i;
            }
        }

        return $idx;
    }

    private function indice_primeiro($valores) {
        foreach ($valores as $i => $v) {
            if ($v !== null) {
                return $i;
            }
        }

        return null;
    }

    private function indice_ultimo($valores) {
        for ($i = count($valores) - 1; $i >= 0; $i--) {
            if ($valores[$i] !== null) {
                return $i;
            }
        }

        return null;
    }

    /** Taxas, índices e valores por pessoa podem ser comparados entre abrangências; totais, não. */
    private function comparavel($g) {
        if (in_array($g['formato'], ['percentual', 'indice', 'decimal1', 'decimal2'], true)) {
            return true;
        }

        return $g['formato'] === 'moeda' && mb_stripos((string) $g['unidade'], 'mil') === false;
    }

    private function lista($itens) {
        if (count($itens) <= 1) {
            return implode('', $itens);
        }
        $ultimo = array_pop($itens);

        return implode(', ', $itens) . ' e ' . $ultimo;
    }
}
