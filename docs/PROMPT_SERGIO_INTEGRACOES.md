# Integrações de dados dos relatórios municipais — o que falta fazer

Para: Sérgio (ou o agente de código que ele usar).
Contexto: proposta "Evolução da Plataforma SXDATA", frente A (camada de
integração) e frente B (ampliação das informações, incluindo saúde).

O restante da proposta já está implementado no painel. Falta a parte que
busca os dados nas fontes. Este documento explica onde ela se encaixa, o
formato que o painel espera e o que entregar.

---

## 1. O que já existe

Painel: CodeIgniter 3 + PostgreSQL, repositório `leoharley/sxdata`, publicado
em `https://painel.sxdata.com.br` (deploy pelo Git do cPanel, a partir do
`main`).

Menu **Relatórios Automatizados** (administrador e supervisor):

1. **Gerar relatório**: município (código IBGE de 7 dígitos), período e eixos temáticos.
2. **Processamento**: roda os conectores e mostra o status de cada fonte.
3. **Dados**: mostra tudo o que foi gravado e permite complementar à mão.
4. **Revisão**: gráficos, textos e aprovação por seção e por gráfico.
5. **Aprovação final** e **documento** em PDF para o cliente.

Hoje **nenhum conector está registrado**. O relatório funciona apenas com
dados digitados na tela Dados. O seu trabalho é preencher esses dados
automaticamente.

### Arquivos que importam para você

| Arquivo | Papel |
|---|---|
| `application/config/relatorio_catalogo.php` | **O contrato.** Eixos, chaves dos indicadores e gráficos que usam cada chave. |
| `application/config/relatorio_conectores.php` | Onde você registra os conectores. |
| `assets/js/relatorios/conectores.js` | API dos conectores JavaScript (`SXConectores`). Não precisa alterar. |
| `assets/js/relatorios/conectores/_modelo.js` | Modelo comentado de conector JS. |
| `application/libraries/Relatorio_conector.php` | Classe base dos conectores PHP. |
| `sql/municipal_reports_migration.sql` | Tabelas do módulo (`report_indicators` é onde os dados ficam). |

---

## 2. O contrato: linhas de indicador

Todo conector devolve uma **lista de linhas**. O painel valida cada linha
contra o catálogo, grava e mostra quantas foram aceitas e quais foram
recusadas, com o motivo.

```js
{
  indicador:     'populacao_estimada',  // chave do catálogo (obrigatório)
  abrangencia:   'municipio',           // 'municipio' | 'estado' | 'brasil' (obrigatório)
  periodo:       '2025',                // ano ou rótulo; '' se não se aplica
  categoria:     '',                    // subdivisão; '' se não se aplica
  valor:         38500,                 // número; aceita também "1.234,5"
  valor_texto:   null,                  // só para indicadores tipo 'texto'
  fonte:         'IBGE - Estimativas populacionais (agregado 6579)', // opcional; usa a do catálogo se faltar
  periodo_fonte: '2025'                 // opcional: referência citada pela fonte
}
```

Regras:

- **A chave do indicador tem que existir no catálogo.** Chave desconhecida é
  recusada. Para um indicador novo, acrescente-o ao catálogo (e, se for
  desenhado, um gráfico que o use). Nenhum outro arquivo precisa mudar.
- A combinação indicador + abrangência + período + categoria é única. Gravar
  de novo substitui o valor: rodar o conector duas vezes não duplica nada.
- Categorias seguem o que o catálogo indica no campo `sugestao`. Exemplos:
  - pirâmide etária: `"20 a 24 anos|Homens"`, `"20 a 24 anos|Mulheres"`;
  - saneamento: `"Água (rede geral)"`, `"Esgoto (rede coletora)"`, `"Coleta de resíduos"`.
- Sempre que a fonte tiver, envie **estado e Brasil** junto do município. Os
  gráficos de taxas, índices e valores per capita comparam os três. Para
  totais (população, PIB), o gráfico mostra só o município.
- Valores ausentes na fonte (`'...'`, `'-'`, `'X'` do SIDRA) não viram linha.
  `SXConectores.numero()` já devolve `null` para eles.

---

## 3. Conectores JavaScript (APIs públicas)

Use para fontes que o navegador consegue chamar: IBGE/SIDRA
(`apisidra.ibge.gov.br`), `servicodados.ibge.gov.br` e outras APIs que liberam
CORS. Rodam na tela de Processamento, no navegador de quem gerou o relatório,
na origem `https://painel.sxdata.com.br`. **Dá para reaproveitar quase
diretamente as funções `get*` da página IBGE Panorama.**

```js
// assets/js/relatorios/conectores/ibge_populacao.js
SXConectores.registrar({
    chave: 'ibge_populacao',                 // único e estável
    rotulo: 'IBGE - População e território', // aparece na tela de processamento
    eixos: ['territorio_populacao'],         // só roda se o relatório incluir um deles

    executar: async function (ctx) {
        // ctx.codigoIbge '5300108' · ctx.codigoUf '53' · ctx.uf 'DF'
        // ctx.municipio 'Brasília' · ctx.periodo '2026' · ctx.eixos [...]
        const serie = await getEvolucaoPopulacional(ctx.codigoIbge, ctx.codigoUf);
        // { municipio: {'2017': ..}, estado: {..}, brasil: {..} } -> linhas
        return SXConectores.serie('populacao_estimada', serie, {
            fonte: 'IBGE - Estimativas populacionais (agregado 6579)'
        });
    }
});
```

Funções auxiliares disponíveis:

| Função | Uso |
|---|---|
| `SXConectores.serie(indicador, {municipio, estado, brasil}, extra)` | Séries no formato que suas funções já devolvem. |
| `SXConectores.categorias(indicador, abrangencia, periodo, {cat: valor}, extra)` | Valores por categoria. |
| `SXConectores.texto(indicador, texto, extra)` | Informação qualitativa (histórico, política ambiental...). |
| `SXConectores.linha(indicador, abrangencia, periodo, valor, extra)` | Uma linha avulsa. |
| `SXConectores.numero(v)` | Converte `"1.234,5"`, `"1234.5"`; marcadores do SIDRA viram `null`. |

Registro, em `application/config/relatorio_conectores.php`:

```php
$config['relatorio_conectores_js'] = ['ibge_comum.js', 'ibge_populacao.js', 'ibge_economia.js'];
```

A ordem importa: um arquivo de funções compartilhadas (`ibge_comum.js`) pode
vir primeiro e ser usado pelos demais.

Cuidados ao portar o código da página IBGE Panorama:

- **Não use `localStorage` nem os caches da página.** Cada execução deve buscar
  dados atuais; o painel já guarda o resultado.
- **Arquivos locais**, como o `ideb.json` lido por `getIDEB`: coloque-os em
  `assets/js/relatorios/dados/` e busque pelo caminho absoluto
  (`/assets/js/relatorios/dados/ideb.json`).
- **Erro com mensagem legível.** Se a fonte falhar, lance
  `throw new Error('IBGE indisponível (HTTP 503)')`. A mensagem aparece para a
  equipe, e os demais conectores continuam.
- **Funções de localização por IP** (`getIPAndLocation`) e de log de acesso da
  página **não devem ser portadas**.
- Limite chamadas simultâneas. O SIDRA recusa rajadas grandes; agrupe as
  consultas em blocos.

---

## 4. Conectores PHP (e-SUS e fontes sem CORS)

Use para o que o navegador não alcança:

- **base local do e-SUS** no servidor do município (APS e média e alta complexidade);
- fontes que não liberam CORS (TABNET/DATASUS, por exemplo);
- APIs que exigem chave.

```php
// application/libraries/conectores/Conector_esus_aps.php
class Conector_esus_aps extends Relatorio_conector {
    public function chave()  { return 'esus_aps'; }
    public function rotulo() { return 'e-SUS APS (base municipal)'; }
    public function eixos()  { return ['saude_aps']; }

    public function executar($relatorio) {
        // $relatorio->codigo_ibge, ->municipio_nome, ->uf, ->periodo_referencia
        // Conecte SOMENTE LEITURA; credenciais no .env, nunca no código.
        $linhas = [];
        $linhas[] = $this->linha('equipes_esf', 'municipio', '2025', 8, ['fonte' => 'e-SUS APS']);
        return $linhas;
    }
}
```

Registro:

```php
$config['relatorio_conectores_php'] = ['Conector_esus_aps', 'Conector_esus_mac'];
```

Para o e-SUS:

- O cliente fornece o acesso de leitura (credenciais, VPN, endereço e porta) e
  a autorização da prefeitura. Sem isso, os eixos de APS e de média e alta
  complexidade ficam só com dados de fontes abertas (seção 16 da proposta).
- **Somente leitura**, sem alterar nada na base do município.
- **Credenciais no `.env` do servidor** (ex.: `ESUS_<COD_IBGE>_HOST`,
  `ESUS_<COD_IBGE>_USER`...). Nunca em arquivo versionado. O `.env` já está no
  `.gitignore`.
- **Um município, uma base.** O conector usa `$relatorio->codigo_ibge` para
  escolher a conexão e deve lançar exceção clara quando não houver credencial
  configurada para aquele município.
- **Tempo de execução.** Os conectores PHP rodam numa única requisição. Se
  alguma consulta passar de ~30 s, avise: dá para mover para execução em
  segundo plano.

---

## 5. Lista de municípios (busca na criação do relatório)

A tabela `ibge_municipios` (código, nome, UF, código da UF) alimenta a busca
de município. Hoje está vazia, e a equipe digita nome e código à mão.

Entregar um `sql/ibge_municipios_seed.sql` gerado a partir de
`https://servicodados.ibge.gov.br/api/v1/localidades/municipios`:

```sql
INSERT INTO ibge_municipios (codigo, nome, uf, codigo_uf) VALUES
  (5300108, 'Brasília', 'DF', 53),
  ...
ON CONFLICT (codigo) DO UPDATE SET nome = EXCLUDED.nome, uf = EXCLUDED.uf, codigo_uf = EXCLUDED.codigo_uf;
```

---

## 6. O que entregar

1. Conectores cobrindo os indicadores do catálogo, eixo por eixo. Cada
   indicador tem no campo `sugestao` a função da página IBGE Panorama que já o
   obtém.
2. Conectores PHP do e-SUS para `saude_aps` e `saude_mac`, quando o acesso for
   liberado.
3. `sql/ibge_municipios_seed.sql`.
4. **Uma lista dos indicadores que nenhuma fonte cobre**, com o motivo.
   Esses ficam para complementação manual, e a equipe precisa saber quais são.

### Como testar

1. Rode `sql/municipal_reports_migration.sql` num banco de teste, se ainda não
   rodou.
2. Gere um relatório de um município que você conheça os números (ex.: o
   código do seu teste na página IBGE Panorama).
3. Na tela de **Processamento**, cada conector precisa terminar com ✔ e
   "N valor(es) gravado(s)". Linhas recusadas aparecem com o motivo.
4. Confira os valores na tela **Dados** contra a fonte.
5. Na **Revisão**, os gráficos daquele eixo devem aparecer preenchidos, com a
   fonte certa.

### O que não fazer

- Não gravar direto no banco. O painel valida, grava e registra o status.
- Não renomear chaves do catálogo já usadas. Os gráficos dependem delas;
  renomear exige ajustar o gráfico junto.
- Não versionar credenciais.

### Dúvidas sobre a parte já implementada

O fluxo completo está em `application/controllers/Relatorios_municipais.php`.
A montagem dos gráficos a partir das linhas está em
`application/libraries/Relatorio_graficos.php`; lá fica claro como cada `modo`
de gráfico (série, categorias, ranking, composição, pirâmide) lê as linhas.
