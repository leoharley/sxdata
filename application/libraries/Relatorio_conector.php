<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Base dos conectores de dados que rodam no servidor.
 *
 * Use para fontes que o navegador não alcança: a base local do e-SUS no
 * servidor do município, APIs com chave ou sem CORS. Para APIs públicas
 * acessíveis pelo navegador, prefira um conector JavaScript
 * (assets/js/relatorios/conectores/).
 *
 * Como criar um conector:
 *   1. application/libraries/conectores/Conector_xxx.php com
 *      class Conector_xxx extends Relatorio_conector
 *   2. Registrar 'Conector_xxx' em application/config/relatorio_conectores.php
 *
 * O conector só LÊ a fonte e devolve linhas. Gravar, validar contra o
 * catálogo e registrar o status é feito pelo módulo.
 */
abstract class Relatorio_conector {

    /** Identificador único e estável, ex.: 'esus_aps'. */
    abstract public function chave();

    /** Nome exibido na tela de processamento, ex.: 'e-SUS APS (base municipal)'. */
    abstract public function rotulo();

    /** Eixos do catálogo atendidos; o conector só roda se o relatório incluir algum. */
    abstract public function eixos();

    /**
     * Busca os dados do município.
     *
     * @param object $relatorio linha de municipal_reports: codigo_ibge (7 dígitos),
     *                          municipio_nome, uf, periodo_referencia, eixos (JSON)
     * @return array lista de linhas; monte cada uma com linha()
     * @throws Exception com mensagem legível quando a fonte falhar; ela aparece
     *                   para a equipe na tela de processamento
     */
    abstract public function executar($relatorio);

    /**
     * Monta uma linha no formato aceito pelo módulo.
     *
     * @param string     $indicador   chave do catálogo
     * @param string     $abrangencia municipio | estado | brasil
     * @param string     $periodo     ano ou rótulo; '' se não se aplica
     * @param float|null $valor       valor numérico (NULL se for só texto)
     * @param array      $extra       categoria, valor_texto, unidade, fonte, periodo_fonte
     */
    protected function linha($indicador, $abrangencia, $periodo, $valor, array $extra = []) {
        return array_merge([
            'indicador'   => $indicador,
            'abrangencia' => $abrangencia,
            'periodo'     => (string) $periodo,
            'valor'       => $valor,
        ], $extra);
    }
}
