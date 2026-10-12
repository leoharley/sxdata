/**
 * MODELO de conector JavaScript — copie este arquivo, renomeie e registre o
 * nome em application/config/relatorio_conectores.php
 * ($config['relatorio_conectores_js']).
 *
 * Este arquivo NÃO está registrado e não roda.
 *
 * Regras:
 *  - Use as chaves de indicador de application/config/relatorio_catalogo.php.
 *    Chave desconhecida é recusada (e aparece como aviso na tela).
 *  - Devolva linhas; quem grava é o painel. Não chame o servidor daqui.
 *  - Em caso de falha da fonte, lance um Error com mensagem legível: ela
 *    aparece para a equipe na tela de processamento.
 *  - Funções da página IBGE Panorama podem ser copiadas para dentro deste
 *    arquivo (ou para um arquivo compartilhado registrado antes dele).
 */
(function () {
    'use strict';

    /*
    SXConectores.registrar({
        chave: 'ibge_populacao',
        rotulo: 'IBGE - Estimativas populacionais',
        eixos: ['territorio_populacao'],

        executar: async function (ctx) {
            var base = 'https://servicodados.ibge.gov.br/api/v3/agregados/6579/periodos/2017|2018|2019|2020|2021|2024|2025/variaveis/9324';
            var urls = {
                municipio: base + '?localidades=N6[' + ctx.codigoIbge + ']',
                estado:    base + '?localidades=N3[' + ctx.codigoUf + ']',
                brasil:    base + '?localidades=N1[1]'
            };

            var serie = {};
            for (var abr in urls) {
                var resp = await fetch(urls[abr]);
                if (!resp.ok) throw new Error('IBGE indisponível (HTTP ' + resp.status + ')');
                var json = await resp.json();
                serie[abr] = (json[0] && json[0].resultados[0].series[0].serie) || {};
            }

            // { municipio: { '2017': '35100', ... }, estado: {...}, brasil: {...} } -> linhas
            return SXConectores.serie('populacao_estimada', serie, {
                fonte: 'IBGE - Estimativas populacionais (agregado 6579)'
            });
        }
    });
    */
})();
