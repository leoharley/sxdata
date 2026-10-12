<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| ============================================================================
| Catálogo dos relatórios municipais
| ============================================================================
|
| Contrato entre a integração (conectores que buscam os dados) e o restante
| do módulo (gráficos, textos, revisão e relatório final).
|
| Os conectores gravam valores com as CHAVES de indicador definidas aqui. Os
| gráficos leem essas chaves. Para acrescentar um indicador ou gráfico, basta
| declará-lo aqui: nenhuma outra parte do código precisa mudar.
|
| Dimensões de cada valor gravado:
|   abrangencia  municipio | estado | brasil
|   periodo      ano ou rótulo do período ('2022', '2024'); '' se não se aplica
|   categoria    subdivisão do indicador ('Indústria', '20 a 24 anos|Homens');
|                '' se não se aplica
|
| 'sugestao' indica onde o dado é obtido hoje na página IBGE Panorama
| (hellou.com.br/ibge-panorama.html), para o conector reaproveitar.
*/

/*
| ----------------------------------------------------------------------------
| Eixos temáticos (cada um vira uma seção do relatório)
| ----------------------------------------------------------------------------
| kpis: indicadores exibidos como números de destaque no topo da seção
*/
$config['relatorio_eixos'] = [
    'caracterizacao' => [
        'titulo' => 'Caracterização do município',
        'icone'  => 'fa-landmark',
        'kpis'   => ['area_territorial', 'populacao_censo'],
    ],
    'territorio_populacao' => [
        'titulo' => 'Território e população',
        'icone'  => 'fa-users',
        'kpis'   => ['populacao_estimada', 'densidade_demografica', 'domicilios'],
    ],
    'economia_trabalho' => [
        'titulo' => 'Economia e trabalho',
        'icone'  => 'fa-chart-line',
        'kpis'   => ['pib', 'pib_per_capita', 'renda_domiciliar_per_capita'],
    ],
    'educacao' => [
        'titulo' => 'Educação',
        'icone'  => 'fa-graduation-cap',
        'kpis'   => ['ideb_anos_iniciais', 'taxa_escolarizacao_6_14', 'taxa_analfabetismo'],
    ],
    'social_gestao' => [
        'titulo' => 'Social e gestão pública',
        'icone'  => 'fa-hands-helping',
        'kpis'   => ['idhm', 'ivs'],
    ],
    'meio_ambiente' => [
        'titulo' => 'Meio ambiente',
        'icone'  => 'fa-leaf',
        'kpis'   => [],
    ],
    'saude' => [
        'titulo' => 'Saúde - Estrutura, morbidade e mortalidade',
        'icone'  => 'fa-heartbeat',
        'kpis'   => ['estabelecimentos_saude', 'leitos_por_mil', 'mortalidade_infantil'],
    ],
    'saude_aps' => [
        'titulo' => 'Saúde - Atenção Primária',
        'icone'  => 'fa-clinic-medical',
        'kpis'   => ['ubs_quantidade', 'equipes_esf', 'cobertura_aps'],
    ],
    'saude_mac' => [
        'titulo' => 'Saúde - Média e Alta Complexidade',
        'icone'  => 'fa-hospital',
        'kpis'   => ['estabelecimentos_mac', 'leitos_sus', 'internacoes_total'],
    ],
];

/*
| ----------------------------------------------------------------------------
| Indicadores
| ----------------------------------------------------------------------------
| tipo     numero (padrão) | texto (informação qualitativa, em valor_texto)
| formato  inteiro | decimal1 | decimal2 | percentual | moeda | indice
*/
$config['relatorio_indicadores'] = [
    // --- Caracterização -----------------------------------------------------
    'historia'            => ['eixo' => 'caracterizacao', 'rotulo' => 'Histórico', 'tipo' => 'texto',
                              'fonte' => 'IBGE Cidades', 'sugestao' => 'getHistoria'],
    'quadro_politico'     => ['eixo' => 'caracterizacao', 'rotulo' => 'Quadro político', 'tipo' => 'texto',
                              'fonte' => 'TSE', 'sugestao' => 'getPolitica / carregarDadosTSE'],
    'area_territorial'    => ['eixo' => 'caracterizacao', 'rotulo' => 'Área territorial', 'unidade' => 'km²',
                              'formato' => 'decimal2', 'fonte' => 'IBGE', 'sugestao' => 'getAreaTerritorial'],
    'populacao_censo'     => ['eixo' => 'caracterizacao', 'rotulo' => 'População (Censo)', 'unidade' => 'habitantes',
                              'formato' => 'inteiro', 'fonte' => 'IBGE - Censo Demográfico', 'sugestao' => 'renderCenso'],

    // --- Território e população --------------------------------------------
    'populacao_estimada'  => ['eixo' => 'territorio_populacao', 'rotulo' => 'População estimada', 'unidade' => 'habitantes',
                              'formato' => 'inteiro', 'fonte' => 'IBGE - Estimativas populacionais',
                              'sugestao' => 'getEvolucaoPopulacional (agregado 6579, variável 9324)'],
    'densidade_demografica' => ['eixo' => 'territorio_populacao', 'rotulo' => 'Densidade demográfica', 'unidade' => 'hab/km²',
                              'formato' => 'decimal2', 'fonte' => 'IBGE', 'sugestao' => 'renderDensidadeDemografica'],
    'taxa_urbanizacao'    => ['eixo' => 'territorio_populacao', 'rotulo' => 'Taxa de urbanização', 'unidade' => '%',
                              'formato' => 'percentual', 'fonte' => 'IBGE - Censo (tabela 200)', 'sugestao' => 'getDemografia (situação do domicílio)'],
    'populacao_faixa_sexo' => ['eixo' => 'territorio_populacao', 'rotulo' => 'População por faixa etária e sexo', 'unidade' => 'habitantes',
                              'formato' => 'inteiro', 'fonte' => 'IBGE - Censo (tabelas 1378 e 9514)',
                              'sugestao' => 'getDemografia (pirâmide). Categoria no formato "faixa|Homens" ou "faixa|Mulheres"'],
    'domicilios'          => ['eixo' => 'territorio_populacao', 'rotulo' => 'Domicílios particulares ocupados', 'unidade' => 'domicílios',
                              'formato' => 'inteiro', 'fonte' => 'IBGE - Censo', 'sugestao' => 'getDomicilios'],
    'densidade_domiciliar' => ['eixo' => 'territorio_populacao', 'rotulo' => 'Moradores por domicílio', 'unidade' => 'moradores',
                              'formato' => 'decimal2', 'fonte' => 'IBGE - Censo', 'sugestao' => 'renderDensidadeDomiciliar'],
    'saneamento_basico'   => ['eixo' => 'territorio_populacao', 'rotulo' => 'Saneamento básico (domicílios atendidos)', 'unidade' => '%',
                              'formato' => 'percentual', 'fonte' => 'IBGE - Censo 2022 (tabelas 6803, 6805, 6892)',
                              'sugestao' => 'getSaneamento2022. Categorias: "Água (rede geral)", "Esgoto (rede coletora)", "Coleta de resíduos"'],

    // --- Economia e trabalho -----------------------------------------------
    'pib'                 => ['eixo' => 'economia_trabalho', 'rotulo' => 'PIB', 'unidade' => 'R$ mil',
                              'formato' => 'moeda', 'fonte' => 'IBGE - PIB dos Municípios (tabela 5938)', 'sugestao' => 'getPIBSidra'],
    'pib_per_capita'      => ['eixo' => 'economia_trabalho', 'rotulo' => 'PIB per capita', 'unidade' => 'R$',
                              'formato' => 'moeda', 'fonte' => 'IBGE - PIB dos Municípios', 'sugestao' => 'getPIBSidra'],
    'pib_composicao'      => ['eixo' => 'economia_trabalho', 'rotulo' => 'Composição da economia (valor adicionado)', 'unidade' => 'R$ mil',
                              'formato' => 'moeda', 'fonte' => 'IBGE - PIB dos Municípios (tabela 5938)',
                              'sugestao' => 'getPIBSidra. Categorias: Agropecuária, Indústria, Serviços, Administração pública, Impostos'],
    'renda_domiciliar_per_capita' => ['eixo' => 'economia_trabalho', 'rotulo' => 'Rendimento domiciliar per capita', 'unidade' => 'R$',
                              'formato' => 'moeda', 'fonte' => 'IBGE - Censo 2022', 'sugestao' => 'getRenda2022'],
    'pea'                 => ['eixo' => 'economia_trabalho', 'rotulo' => 'População economicamente ativa', 'unidade' => 'pessoas',
                              'formato' => 'inteiro', 'fonte' => 'IBGE - Censo 2022', 'sugestao' => 'getPEA2022'],
    'taxa_ocupacao'       => ['eixo' => 'economia_trabalho', 'rotulo' => 'Taxa de ocupação', 'unidade' => '%',
                              'formato' => 'percentual', 'fonte' => 'IBGE', 'sugestao' => 'getOcupacao'],
    'ocupados_por_setor'  => ['eixo' => 'economia_trabalho', 'rotulo' => 'Pessoas ocupadas por setor', 'unidade' => 'pessoas',
                              'formato' => 'inteiro', 'fonte' => 'MTE - CAGED / IBGE', 'sugestao' => 'getSetoresCaged / getPosicaoOcupacao'],
    'empresas'            => ['eixo' => 'economia_trabalho', 'rotulo' => 'Unidades locais (empresas)', 'unidade' => 'unidades',
                              'formato' => 'inteiro', 'fonte' => 'IBGE - CEMPRE', 'sugestao' => 'getEstatisticaEmpresas'],
    'frota_veiculos'      => ['eixo' => 'economia_trabalho', 'rotulo' => 'Frota de veículos', 'unidade' => 'veículos',
                              'formato' => 'inteiro', 'fonte' => 'SENATRAN', 'sugestao' => 'getFrotasVeiculos. Categorias: tipo de veículo'],
    'producao_agricola'   => ['eixo' => 'economia_trabalho', 'rotulo' => 'Produção agrícola (valor)', 'unidade' => 'R$ mil',
                              'formato' => 'moeda', 'fonte' => 'IBGE - PAM', 'sugestao' => 'getPAM / getAgricultura. Categorias: produto'],

    // --- Educação -----------------------------------------------------------
    'ideb_anos_iniciais'  => ['eixo' => 'educacao', 'rotulo' => 'IDEB - Anos iniciais', 'formato' => 'decimal1',
                              'fonte' => 'INEP', 'sugestao' => 'getIDEB (anosIniciais)'],
    'ideb_anos_finais'    => ['eixo' => 'educacao', 'rotulo' => 'IDEB - Anos finais', 'formato' => 'decimal1',
                              'fonte' => 'INEP', 'sugestao' => 'getIDEB (anosFinais)'],
    'ideb_ensino_medio'   => ['eixo' => 'educacao', 'rotulo' => 'IDEB - Ensino médio', 'formato' => 'decimal1',
                              'fonte' => 'INEP', 'sugestao' => 'getIDEB (ensinoMedio)'],
    'taxa_analfabetismo'  => ['eixo' => 'educacao', 'rotulo' => 'Taxa de analfabetismo (15 anos ou mais)', 'unidade' => '%',
                              'formato' => 'percentual', 'fonte' => 'IBGE - Censo', 'sugestao' => 'getAnalfabetismo'],
    'taxa_escolarizacao_6_14' => ['eixo' => 'educacao', 'rotulo' => 'Escolarização de 6 a 14 anos', 'unidade' => '%',
                              'formato' => 'percentual', 'fonte' => 'IBGE - Censo', 'sugestao' => 'getAtendimentoEscolar'],
    'matriculas_etapa'    => ['eixo' => 'educacao', 'rotulo' => 'Matrículas por etapa de ensino', 'unidade' => 'matrículas',
                              'formato' => 'inteiro', 'fonte' => 'INEP - Censo Escolar', 'sugestao' => 'getEstabelecimentosEnsino. Categorias: etapa'],
    'politica_educacao'   => ['eixo' => 'educacao', 'rotulo' => 'Política e ações de educação', 'tipo' => 'texto',
                              'fonte' => 'Levantamento municipal', 'sugestao' => 'getPoliticaEducacao / getAcoesEducacao'],

    // --- Social e gestão pública -------------------------------------------
    'idhm'                => ['eixo' => 'social_gestao', 'rotulo' => 'IDHM', 'formato' => 'indice',
                              'fonte' => 'Atlas Brasil (PNUD)', 'sugestao' => 'getDesenvolvimentoHumano / getAtlasBrasil'],
    'idhm_educacao'       => ['eixo' => 'social_gestao', 'rotulo' => 'IDHM Educação', 'formato' => 'indice', 'fonte' => 'Atlas Brasil (PNUD)'],
    'idhm_longevidade'    => ['eixo' => 'social_gestao', 'rotulo' => 'IDHM Longevidade', 'formato' => 'indice', 'fonte' => 'Atlas Brasil (PNUD)'],
    'idhm_renda'          => ['eixo' => 'social_gestao', 'rotulo' => 'IDHM Renda', 'formato' => 'indice', 'fonte' => 'Atlas Brasil (PNUD)'],
    'ivs'                 => ['eixo' => 'social_gestao', 'rotulo' => 'Índice de Vulnerabilidade Social', 'formato' => 'indice',
                              'fonte' => 'IPEA', 'sugestao' => 'getVulnerabilidade / carregarVulnerabilidadeExtra'],
    'gestao_fiscal'       => ['eixo' => 'social_gestao', 'rotulo' => 'Gestão fiscal (IFGF)', 'formato' => 'indice',
                              'fonte' => 'FIRJAN - IFGF', 'sugestao' => 'getGestaoFiscal. Categorias: Autonomia, Gastos com pessoal, Liquidez, Investimentos'],
    'taxa_homicidios'     => ['eixo' => 'social_gestao', 'rotulo' => 'Taxa de homicídios', 'unidade' => 'por 100 mil hab.',
                              'formato' => 'decimal1', 'fonte' => 'Atlas da Violência', 'sugestao' => 'getSeguranca'],
    'assistencia_social'  => ['eixo' => 'social_gestao', 'rotulo' => 'Assistência social', 'tipo' => 'texto',
                              'fonte' => 'Levantamento municipal', 'sugestao' => 'getAssistenciaSocial'],

    // --- Meio ambiente ------------------------------------------------------
    'informacoes_ambientais' => ['eixo' => 'meio_ambiente', 'rotulo' => 'Informações ambientais', 'tipo' => 'texto',
                              'fonte' => 'IBGE', 'sugestao' => 'getMeioAmbiente'],
    'politica_ambiental'  => ['eixo' => 'meio_ambiente', 'rotulo' => 'Política ambiental', 'tipo' => 'texto',
                              'fonte' => 'IBGE - MUNIC', 'sugestao' => 'getPoliticaAmbiental'],
    'coleta_seletiva'     => ['eixo' => 'meio_ambiente', 'rotulo' => 'Coleta seletiva', 'tipo' => 'texto',
                              'fonte' => 'SNIS / IBGE - MUNIC', 'sugestao' => 'getColetaSeletiva'],
    'esgotamento_adequado' => ['eixo' => 'meio_ambiente', 'rotulo' => 'Esgotamento sanitário adequado', 'unidade' => '%',
                              'formato' => 'percentual', 'fonte' => 'IBGE - Censo 2022', 'sugestao' => 'getSaneamento2022'],

    // --- Saúde (estrutura, morbidade, mortalidade) -------------------------
    'estabelecimentos_saude' => ['eixo' => 'saude', 'rotulo' => 'Estabelecimentos de saúde', 'unidade' => 'estabelecimentos',
                              'formato' => 'inteiro', 'fonte' => 'CNES / DATASUS', 'sugestao' => 'getEstruturaSaude'],
    'estabelecimentos_nivel' => ['eixo' => 'saude', 'rotulo' => 'Estabelecimentos por nível de atenção', 'unidade' => 'estabelecimentos',
                              'formato' => 'inteiro', 'fonte' => 'CNES / DATASUS',
                              'sugestao' => 'getEstruturaSaude. Categorias: Atenção básica, Média complexidade, Alta complexidade'],
    'leitos_por_mil'      => ['eixo' => 'saude', 'rotulo' => 'Leitos hospitalares por mil habitantes', 'unidade' => 'leitos/mil hab.',
                              'formato' => 'decimal1', 'fonte' => 'CNES / DATASUS', 'sugestao' => 'getEstruturaSaude'],
    'taxa_mortalidade_geral' => ['eixo' => 'saude', 'rotulo' => 'Taxa de mortalidade geral', 'unidade' => 'por mil hab.',
                              'formato' => 'decimal1', 'fonte' => 'DATASUS - SIM', 'sugestao' => 'getTaxasMortalidade'],
    'mortalidade_infantil' => ['eixo' => 'saude', 'rotulo' => 'Mortalidade infantil', 'unidade' => 'por mil nascidos vivos',
                              'formato' => 'decimal1', 'fonte' => 'DATASUS - SIM/SINASC', 'sugestao' => 'getTaxasMortalidade / carregarAtlasMortalidade'],
    'internacoes_por_causa' => ['eixo' => 'saude', 'rotulo' => 'Internações por grupo de causas (CID-10)', 'unidade' => 'internações',
                              'formato' => 'inteiro', 'fonte' => 'DATASUS - SIH/SUS', 'sugestao' => 'getMorbidade. Categorias: capítulo do CID-10'],
    'incidencia_aids'     => ['eixo' => 'saude', 'rotulo' => 'Incidência de AIDS', 'unidade' => 'por 100 mil hab.',
                              'formato' => 'decimal1', 'fonte' => 'Ministério da Saúde', 'sugestao' => 'getIncidenciaAids'],
    'politica_saude'      => ['eixo' => 'saude', 'rotulo' => 'Política de saúde', 'tipo' => 'texto',
                              'fonte' => 'Levantamento municipal', 'sugestao' => 'getPoliticaSaude'],

    // --- Saúde - Atenção Primária (e-SUS municipal + CNES) ------------------
    'ubs_quantidade'      => ['eixo' => 'saude_aps', 'rotulo' => 'Unidades Básicas de Saúde', 'unidade' => 'UBS',
                              'formato' => 'inteiro', 'fonte' => 'CNES / e-SUS'],
    'equipes_esf'         => ['eixo' => 'saude_aps', 'rotulo' => 'Equipes de Saúde da Família', 'unidade' => 'equipes',
                              'formato' => 'inteiro', 'fonte' => 'CNES / e-SUS'],
    'cobertura_aps'       => ['eixo' => 'saude_aps', 'rotulo' => 'Cobertura da Atenção Primária', 'unidade' => '%',
                              'formato' => 'percentual', 'fonte' => 'e-Gestor AB / e-SUS'],
    'profissionais_aps'   => ['eixo' => 'saude_aps', 'rotulo' => 'Profissionais da Atenção Primária', 'unidade' => 'profissionais',
                              'formato' => 'inteiro', 'fonte' => 'CNES / e-SUS', 'sugestao' => 'Categorias: categoria profissional'],
    'atendimentos_aps'    => ['eixo' => 'saude_aps', 'rotulo' => 'Atendimentos individuais na APS', 'unidade' => 'atendimentos',
                              'formato' => 'inteiro', 'fonte' => 'e-SUS APS (base municipal)'],
    'procedimentos_aps'   => ['eixo' => 'saude_aps', 'rotulo' => 'Procedimentos realizados na APS', 'unidade' => 'procedimentos',
                              'formato' => 'inteiro', 'fonte' => 'e-SUS APS (base municipal)', 'sugestao' => 'Categorias: tipo de procedimento'],

    // --- Saúde - Média e Alta Complexidade -----------------------------------
    'estabelecimentos_mac' => ['eixo' => 'saude_mac', 'rotulo' => 'Estabelecimentos de média e alta complexidade', 'unidade' => 'estabelecimentos',
                              'formato' => 'inteiro', 'fonte' => 'CNES / DATASUS'],
    'leitos_sus'          => ['eixo' => 'saude_mac', 'rotulo' => 'Leitos SUS', 'unidade' => 'leitos',
                              'formato' => 'inteiro', 'fonte' => 'CNES / DATASUS'],
    'internacoes_total'   => ['eixo' => 'saude_mac', 'rotulo' => 'Internações hospitalares', 'unidade' => 'internações',
                              'formato' => 'inteiro', 'fonte' => 'DATASUS - SIH/SUS'],
    'atendimentos_ambulatoriais_mac' => ['eixo' => 'saude_mac', 'rotulo' => 'Atendimentos ambulatoriais de média e alta complexidade',
                              'unidade' => 'atendimentos', 'formato' => 'inteiro', 'fonte' => 'DATASUS - SIA/SUS'],
    'procedimentos_mac'   => ['eixo' => 'saude_mac', 'rotulo' => 'Procedimentos de média e alta complexidade', 'unidade' => 'procedimentos',
                              'formato' => 'inteiro', 'fonte' => 'DATASUS - SIA/SIH', 'sugestao' => 'Categorias: grupo de procedimento'],
];

/*
| ----------------------------------------------------------------------------
| Gráficos
| ----------------------------------------------------------------------------
| tipo   linha | colunas | barras (horizontais) | rosca | piramide | combinado
| modo   como os valores viram o gráfico:
|   serie        eixo X = períodos; uma série por abrangência (município, estado, Brasil)
|   categorias   eixo X = categorias do período mais recente; uma série por abrangência
|   ranking      categorias do município no período mais recente, da maior para a menor (até 10)
|   composicao   participação de cada categoria do município (%), período mais recente
|   piramide     categorias "faixa|Homens" e "faixa|Mulheres" do município, em % da população
|   indicadores  eixo X = vários indicadores, período mais recente; uma série por abrangência
|   combinado    categorias do município: quantidade (colunas) e % do total (linha)
*/
$config['relatorio_graficos'] = [
    'evolucao_populacional' => ['eixo' => 'territorio_populacao', 'titulo' => 'Evolução populacional',
                                'tipo' => 'linha', 'modo' => 'serie', 'indicador' => 'populacao_estimada'],
    'taxa_urbanizacao'      => ['eixo' => 'territorio_populacao', 'titulo' => 'Taxa de urbanização (%)',
                                'tipo' => 'colunas', 'modo' => 'serie', 'indicador' => 'taxa_urbanizacao'],
    'piramide_etaria'       => ['eixo' => 'territorio_populacao', 'titulo' => 'Pirâmide etária',
                                'tipo' => 'piramide', 'modo' => 'piramide', 'indicador' => 'populacao_faixa_sexo'],
    'saneamento'            => ['eixo' => 'territorio_populacao', 'titulo' => 'Saneamento básico (%)',
                                'tipo' => 'colunas', 'modo' => 'categorias', 'indicador' => 'saneamento_basico'],
    'densidade_domiciliar'  => ['eixo' => 'territorio_populacao', 'titulo' => 'Moradores por domicílio',
                                'tipo' => 'colunas', 'modo' => 'serie', 'indicador' => 'densidade_domiciliar'],

    'evolucao_pib'          => ['eixo' => 'economia_trabalho', 'titulo' => 'Evolução do PIB',
                                'tipo' => 'linha', 'modo' => 'serie', 'indicador' => 'pib'],
    'pib_per_capita'        => ['eixo' => 'economia_trabalho', 'titulo' => 'PIB per capita',
                                'tipo' => 'colunas', 'modo' => 'serie', 'indicador' => 'pib_per_capita'],
    'composicao_economia'   => ['eixo' => 'economia_trabalho', 'titulo' => 'Composição da economia',
                                'tipo' => 'rosca', 'modo' => 'composicao', 'indicador' => 'pib_composicao'],
    'ocupados_por_setor'    => ['eixo' => 'economia_trabalho', 'titulo' => 'Mercado de trabalho por setor',
                                'tipo' => 'barras', 'modo' => 'ranking', 'indicador' => 'ocupados_por_setor'],
    'frota_veiculos'        => ['eixo' => 'economia_trabalho', 'titulo' => 'Frota de veículos',
                                'tipo' => 'barras', 'modo' => 'ranking', 'indicador' => 'frota_veiculos'],
    'producao_agricola'     => ['eixo' => 'economia_trabalho', 'titulo' => 'Principais produtos agrícolas',
                                'tipo' => 'barras', 'modo' => 'ranking', 'indicador' => 'producao_agricola'],

    'ideb_anos_iniciais'    => ['eixo' => 'educacao', 'titulo' => 'IDEB - Anos iniciais do ensino fundamental',
                                'tipo' => 'linha', 'modo' => 'serie', 'indicador' => 'ideb_anos_iniciais'],
    'ideb_anos_finais'      => ['eixo' => 'educacao', 'titulo' => 'IDEB - Anos finais do ensino fundamental',
                                'tipo' => 'linha', 'modo' => 'serie', 'indicador' => 'ideb_anos_finais'],
    'analfabetismo'         => ['eixo' => 'educacao', 'titulo' => 'Taxa de analfabetismo (%)',
                                'tipo' => 'colunas', 'modo' => 'serie', 'indicador' => 'taxa_analfabetismo'],
    'matriculas_etapa'      => ['eixo' => 'educacao', 'titulo' => 'Matrículas por etapa de ensino',
                                'tipo' => 'barras', 'modo' => 'ranking', 'indicador' => 'matriculas_etapa'],

    'idhm'                  => ['eixo' => 'social_gestao', 'titulo' => 'Evolução do IDHM',
                                'tipo' => 'colunas', 'modo' => 'serie', 'indicador' => 'idhm'],
    'idhm_dimensoes'        => ['eixo' => 'social_gestao', 'titulo' => 'IDHM por dimensão',
                                'tipo' => 'barras', 'modo' => 'indicadores',
                                'indicadores' => ['idhm_educacao', 'idhm_longevidade', 'idhm_renda']],
    'gestao_fiscal'         => ['eixo' => 'social_gestao', 'titulo' => 'Gestão fiscal (IFGF)',
                                'tipo' => 'barras', 'modo' => 'categorias', 'indicador' => 'gestao_fiscal'],
    'homicidios'            => ['eixo' => 'social_gestao', 'titulo' => 'Taxa de homicídios',
                                'tipo' => 'linha', 'modo' => 'serie', 'indicador' => 'taxa_homicidios'],

    'esgotamento'           => ['eixo' => 'meio_ambiente', 'titulo' => 'Esgotamento sanitário adequado (%)',
                                'tipo' => 'colunas', 'modo' => 'serie', 'indicador' => 'esgotamento_adequado'],

    'estabelecimentos_nivel' => ['eixo' => 'saude', 'titulo' => 'Estabelecimentos de saúde por nível de atenção',
                                'tipo' => 'combinado', 'modo' => 'combinado', 'indicador' => 'estabelecimentos_nivel'],
    'leitos_por_mil'        => ['eixo' => 'saude', 'titulo' => 'Leitos hospitalares por mil habitantes',
                                'tipo' => 'linha', 'modo' => 'serie', 'indicador' => 'leitos_por_mil'],
    'mortalidade_geral'     => ['eixo' => 'saude', 'titulo' => 'Taxa de mortalidade (por mil habitantes)',
                                'tipo' => 'colunas', 'modo' => 'serie', 'indicador' => 'taxa_mortalidade_geral'],
    'mortalidade_infantil'  => ['eixo' => 'saude', 'titulo' => 'Mortalidade infantil',
                                'tipo' => 'linha', 'modo' => 'serie', 'indicador' => 'mortalidade_infantil'],
    'internacoes_por_causa' => ['eixo' => 'saude', 'titulo' => 'Principais causas de internação',
                                'tipo' => 'barras', 'modo' => 'ranking', 'indicador' => 'internacoes_por_causa'],

    'cobertura_aps'         => ['eixo' => 'saude_aps', 'titulo' => 'Cobertura da Atenção Primária (%)',
                                'tipo' => 'linha', 'modo' => 'serie', 'indicador' => 'cobertura_aps'],
    'atendimentos_aps'      => ['eixo' => 'saude_aps', 'titulo' => 'Atendimentos individuais na APS',
                                'tipo' => 'colunas', 'modo' => 'serie', 'indicador' => 'atendimentos_aps'],
    'profissionais_aps'     => ['eixo' => 'saude_aps', 'titulo' => 'Profissionais da Atenção Primária',
                                'tipo' => 'barras', 'modo' => 'ranking', 'indicador' => 'profissionais_aps'],
    'procedimentos_aps'     => ['eixo' => 'saude_aps', 'titulo' => 'Procedimentos mais realizados na APS',
                                'tipo' => 'barras', 'modo' => 'ranking', 'indicador' => 'procedimentos_aps'],

    'internacoes_mac'       => ['eixo' => 'saude_mac', 'titulo' => 'Internações hospitalares',
                                'tipo' => 'colunas', 'modo' => 'serie', 'indicador' => 'internacoes_total'],
    'ambulatorial_mac'      => ['eixo' => 'saude_mac', 'titulo' => 'Atendimentos ambulatoriais de média e alta complexidade',
                                'tipo' => 'linha', 'modo' => 'serie', 'indicador' => 'atendimentos_ambulatoriais_mac'],
    'procedimentos_mac'     => ['eixo' => 'saude_mac', 'titulo' => 'Procedimentos de média e alta complexidade',
                                'tipo' => 'barras', 'modo' => 'ranking', 'indicador' => 'procedimentos_mac'],
];
