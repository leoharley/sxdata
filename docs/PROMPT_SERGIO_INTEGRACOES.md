# Tarefas do Sérgio — integrações de dados e mapa integrado

Para: Sérgio (ou o agente de código que ele usar).

Este documento tem duas tarefas, nesta ordem:

- **Tarefa 1 — Integrações de dados (seções 1 a 6).** Proposta "Evolução da
  Plataforma SXDATA", frente A (camada de integração) e frente B (ampliação das
  informações, incluindo saúde). O restante da proposta já está implementado no
  painel; falta a parte que busca os dados nas fontes.
- **Tarefa 2 — Mapa integrado de dados secundários e primários (seção 7).**
  Usa os indicadores da Tarefa 1, então vem depois dela.

---

# Tarefa 1 — Integrações de dados dos relatórios municipais

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

---

# Tarefa 2 — Mapa integrado de dados secundários + dados primários (SXDATA — painel web e app)

## 7.1 Contexto
O SXDATA é uma plataforma de coleta de dados com painel web administrativo e aplicativo de campo. Hoje ela já tem formulários/questionários, funcionamento offline, sincronização, captura de fotos, coordenadas geográficas, exportação Excel/KMZ e painéis de consulta. Também já existe uma busca de **dados secundários** (dados oficiais, ex.: IBGE e outras fontes públicas) por município.

O cliente costuma contratar dois tipos de dado juntos:
- **Dados secundários**: indicadores oficiais do município.
- **Dados primários**: o que a equipe coleta em campo (entrevistas, pontos georreferenciados de comunidades etc.).

A feature melhora a busca de dados secundários e junta os dois tipos de dado numa visão de mapa.

## 7.2 Objetivo funcional
1. **Dashboard com mapa (painel web)**
   - O usuário busca um ou mais municípios, que aparecem destacados no mapa (contorno/polígono do município).
   - Um painel de filtros permite buscar **indicadores por tema** (ex.: Saúde, Demografia, Educação, Economia, Saneamento). Escolhido o tema, aparecem os indicadores do município selecionado (valor, ano de referência e fonte).
   - O mapa mostra os **pontos de coleta primária** (ex.: comunidades) que caem naquele município.
   - Ao **clicar num ponto**, abre um popup/painel lateral com os dados coletados ali (respostas do formulário, fotos, data, coletor) e um link para o registro completo.
   - Ao clicar no município, aparecem os indicadores secundários dele.

   Exemplo de uso: o município de Monte do Carmo aparece no mapa com seus indicadores de saúde e demografia. Dentro dele, a comunidade "Andiroba", coletada em campo, aparece como ponto; clicar nela mostra os dados primários coletados.

2. **Aplicativo de campo**
   - O coletor pode **registrar um ponto/comunidade** no mapa (usando o GPS atual ou tocando no mapa), com nome e tipo, e associar a ele as coletas/entrevistas feitas no local.
   - Tem de funcionar **offline**, seguindo o mecanismo de sincronização que já existe, e os pontos devem aparecer no painel depois da sincronização.
   - Se for viável sem grande esforço, o app também mostra o mapa do município com os pontos já sincronizados e um resumo dos indicadores secundários (cacheado para uso offline).

## 7.3 Como trabalhar
1. **Explore antes de codar.** Mapeie a estrutura do painel e do app: stack, módulo de mapas (se já existe algum), modelo de dados das coletas e das coordenadas, a busca atual de dados secundários (de onde vêm os dados, como são cacheados) e o fluxo de sincronização offline.
2. **Apresente um plano curto antes de implementar**, com:
   - mudanças de modelo de dados e migrações (ex.: entidade "ponto/comunidade" ligada ao município pelo código IBGE e às coletas);
   - endpoints novos ou alterados;
   - biblioteca de mapa escolhida (reaproveite a que o projeto já usa; se não houver nenhuma, justifique a escolha);
   - fonte das malhas municipais (ex.: API de malhas do IBGE) e dos indicadores por tema, com uma estratégia de cache;
   - o que entra no painel, o que entra no app e o que fica para uma fase 2.
   **Espere minha aprovação antes de seguir.**
3. Implemente em incrementos pequenos e revisáveis: primeiro backend/modelo, depois o painel, depois o app.

## 7.4 Regras
- Siga os padrões, a arquitetura e as convenções que já existem no projeto. Não introduza frameworks novos sem necessidade.
- Faça mudanças de escopo mínimo e não refatore o que não for necessário para a feature.
- Reaproveite a busca de dados secundários atual em vez de reescrevê-la.
- Não quebre a exportação Excel/KMZ nem a sincronização offline existentes. Se fizer sentido, inclua os novos pontos na exportação KMZ.
- Associe o ponto ao município pelo código IBGE. Quando o ponto tiver coordenadas, valide se ele cai dentro do polígono do município e avise se não cair.
- Na tela, mostre sempre a fonte e o ano de referência de cada indicador.
- Mensagens e textos da interface em português.

## 7.5 Critérios de aceite
- [ ] No painel, buscar "Monte do Carmo" centraliza o mapa no município e destaca o contorno.
- [ ] Filtrar pelo tema "Saúde" ou "Demografia" mostra os indicadores com valor, ano e fonte.
- [ ] Um ponto criado offline no app aparece no mapa do painel depois da sincronização, dentro do município certo.
- [ ] Clicar no ponto mostra os dados primários ligados a ele (respostas, fotos, data, coletor).
- [ ] A sincronização e as exportações que já existem continuam funcionando.
- [ ] Há testes para as regras novas (associação ponto–município, filtro por tema) e eles passam.

## 7.6 Entrega
Ao final, liste os arquivos alterados, as migrações, os endpoints novos, como testar manualmente (passo a passo no painel e no app) e as pendências da fase 2.

## 7.7 Pontos de partida no código atual

Levantados em outubro/2026. Servem para encurtar a exploração do passo 1, não para substituí-la: confira cada um antes de usar.

**Repositórios**
- Painel: `leoharley/sxdata` (CodeIgniter 3 + PostgreSQL). O `main` é publicado pelo Git do cPanel em `https://painel.sxdata.com.br`.
- App: `leoharley/sxdata-app` (Flutter, Android e versão web/PWA). A API usada é `https://painel.sxdata.com.br/api`.
- **Atenção:** o app do SXData compartilha a base de código com o app de outro cliente (Instituto Alves Lima). Ao copiar algo de lá, não traga URL, nomes, logos, pacote (o do SXData é `com.example.sxdata`) nem as cores do outro cliente.

**Mapas**
- Painel: **Leaflet 1.9.4** já carregado em todas as telas (`application/views/admin/footer.php`, junto com `leaflet.heat`). É usado no mapa de calor de `application/views/admin/reports/index.php`.
- App: **flutter_map 6.1** + `latlong2`, com tiles do OpenStreetMap, em `lib/screens/applicators_map_screen.dart`.

**Coordenadas e coletas**
- Cada coleta é uma linha em `form_responses` (`latitude`, `longitude`, `location_name`, `photo_path`, `photo_path_2`, `audio_path`, `applied_by`, `completed_at`). As respostas ficam em `question_responses`.
- A captura de GPS no app fica em `lib/services/location_service.dart`. **Edições nunca alteram as coordenadas de uma coleta**, regra garantida no app e no servidor (`application/controllers/api/Forms.php`). Mantenha isso.
- Coletas por QR Code (`origin = 'qrcode'`) não têm aplicador nem coordenadas.

**Dados secundários**
- Hoje estão em dois lugares: a sua página IBGE Panorama, fora do painel, e o módulo **Relatórios Automatizados**, depois da Tarefa 1.
- No módulo, os indicadores ficam em `report_indicators`, **por relatório**, não por município. Não há cache por município independente de um relatório. Decida no plano se o mapa lê do relatório mais recente do município ou de um cache novo por código IBGE.
- Os eixos de `application/config/relatorio_catalogo.php` (Território e população, Economia, Educação, Saúde...) são candidatos naturais aos "temas" do filtro. Cada indicador já tem fonte, unidade e formato.

**Sincronização offline (app)**
- A fila de envio fica em `lib/providers/form_provider.dart`. Cada coleta tem um `client_id` (UUID gerado no aparelho) que o servidor usa para não duplicar reenvios (`api/forms/submit`, coluna `form_responses.client_id`). Um "ponto/comunidade" criado offline deve seguir o mesmo padrão.
- A coleta só sai da fila quando o servidor devolve o id gravado (`FormResponse.extractServerId`).

**API e permissões**
- Os endpoints do app autenticam com `application/libraries/Api_token.php` (token assinado, 28 dias). Use-a em endpoints novos.
- Telas novas do painel restritas à equipe: siga o padrão `equipe()` de `application/controllers/Relatorios_municipais.php`. Os controllers antigos só verificam o login, sem checar o perfil.

**KMZ**
- `application/libraries/Kmz_generator.php` monta o arquivo em memória; os dados vêm de `Response_model::get_with_location()`.

**Testes**
- App: `flutter test` (93 testes passando em outubro/2026). Para gerar o APK, use o JDK 17: o Gradle 8.12 não funciona com o JDK 25.
- Painel: não há suíte de testes em uso; o PHPUnit está em `require-dev` do `composer.json`. A associação ponto–município (ponto dentro do polígono) pode ser escrita como uma classe PHP sem banco, testável isoladamente.
