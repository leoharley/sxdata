/**
 * Conectores de dados dos relatórios municipais (navegador).
 *
 * Um conector busca dados numa API pública e devolve LINHAS no formato do
 * catálogo (application/config/relatorio_catalogo.php). O painel grava as
 * linhas, valida contra o catálogo e mostra o status de cada fonte.
 *
 * Registro (em assets/js/relatorios/conectores/<arquivo>.js):
 *
 *   SXConectores.registrar({
 *       chave: 'ibge_populacao',               // único e estável
 *       rotulo: 'IBGE - População',            // aparece na tela
 *       eixos: ['territorio_populacao'],       // só roda se o relatório incluir um deles
 *       executar: async function (ctx) {
 *           // ctx.codigoIbge  '5300108' (7 dígitos)
 *           // ctx.codigoUf    '53'
 *           // ctx.uf          'DF'
 *           // ctx.municipio   'Brasília'
 *           // ctx.periodo     '2026'
 *           // ctx.eixos       ['territorio_populacao', ...]
 *           const serie = await getEvolucaoPopulacional(ctx.codigoIbge, ctx.codigoUf);
 *           return SXConectores.serie('populacao_estimada', serie, { fonte: 'IBGE - Estimativas' });
 *       }
 *   });
 *
 * Formato de cada linha:
 *   { indicador, abrangencia: 'municipio'|'estado'|'brasil', periodo, categoria,
 *     valor (número) | valor_texto (texto), unidade, fonte, periodo_fonte }
 */
(function (global) {
    'use strict';

    var registrados = [];

    /**
     * Converte "1.234,5", "1234.5", 1234.5 em número. Marcadores do SIDRA
     * para dado inexistente ('...', '-', 'X', '..') viram null.
     */
    function numero(v) {
        if (v === null || v === undefined) return null;
        if (typeof v === 'number') return isFinite(v) ? v : null;
        var s = String(v).trim();
        if (s === '' || /^(\.{2,3}|-|x|X)$/.test(s)) return null;
        if (/^-?[\d.]+,\d+$/.test(s)) s = s.replace(/\./g, '').replace(',', '.');
        var n = Number(s);
        return isFinite(n) ? n : null;
    }

    function linha(indicador, abrangencia, periodo, valor, extra) {
        var l = { indicador: indicador, abrangencia: abrangencia, periodo: String(periodo == null ? '' : periodo), valor: numero(valor) };
        if (extra) Object.keys(extra).forEach(function (k) { l[k] = extra[k]; });
        return l;
    }

    var SXConectores = {
        registrar: function (def) {
            if (!def || !def.chave || typeof def.executar !== 'function' || !Array.isArray(def.eixos)) {
                console.error('Conector inválido: precisa de chave, eixos e executar()', def);
                return;
            }
            if (registrados.some(function (d) { return d.chave === def.chave; })) {
                console.warn('Conector já registrado: ' + def.chave);
                return;
            }
            registrados.push(def);
        },

        lista: function () { return registrados.slice(); },

        numero: numero,
        linha: linha,

        /**
         * Série histórica no formato das funções da página IBGE Panorama:
         *   { municipio: { '2017': 35100, ... }, estado: {...}, brasil: {...} }
         * Abrangências ausentes ou vazias são ignoradas.
         */
        serie: function (indicador, porAbrangencia, extra) {
            var out = [];
            ['municipio', 'estado', 'brasil'].forEach(function (abr) {
                var serie = porAbrangencia && porAbrangencia[abr];
                if (!serie) return;
                Object.keys(serie).forEach(function (periodo) {
                    var v = numero(serie[periodo]);
                    if (v !== null) out.push(linha(indicador, abr, periodo, v, extra));
                });
            });
            return out;
        },

        /**
         * Valores por categoria num período: { 'Indústria': 80, 'Serviços': 520 }.
         */
        categorias: function (indicador, abrangencia, periodo, porCategoria, extra) {
            var out = [];
            Object.keys(porCategoria || {}).forEach(function (cat) {
                var v = numero(porCategoria[cat]);
                if (v === null) return;
                var l = linha(indicador, abrangencia, periodo, v, extra);
                l.categoria = cat;
                out.push(l);
            });
            return out;
        },

        /** Informação em texto (histórico, política ambiental...). */
        texto: function (indicador, texto, extra) {
            if (!texto || !String(texto).trim()) return [];
            var l = { indicador: indicador, abrangencia: 'municipio', periodo: '', valor: null, valor_texto: String(texto).trim() };
            if (extra) Object.keys(extra).forEach(function (k) { l[k] = extra[k]; });
            return [l];
        }
    };

    // ------------------------------------------------------------------
    // Execução na etapa de processamento
    // ------------------------------------------------------------------

    var LOTE = 300;

    /**
     * @param {object} cfg
     *   relatorio   { id, codigoIbge, codigoUf, uf, municipio, periodo, eixos }
     *   urls        { receber, fonte, servidor }
     *   csrf        { nome, valor } (pode ser vazio)
     *   aoAtualizar function(chave, estado) - estado: { status, mensagem, linhas, avisos }
     */
    SXConectores.executarTodos = async function (cfg) {
        var csrf = cfg.csrf || {};

        function postar(url, campos) {
            var fd = new FormData();
            Object.keys(campos).forEach(function (k) { fd.append(k, campos[k]); });
            if (csrf.nome) fd.append(csrf.nome, csrf.valor);
            return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json().catch(function () { throw new Error('Resposta inválida do servidor (HTTP ' + r.status + ')'); }); })
                .then(function (j) {
                    if (j && j.csrf) csrf.valor = j.csrf; // token renovado a cada requisição, quando o CSRF está ativo
                    if (!j || !j.ok) throw new Error((j && j.mensagem) || 'Falha ao gravar');
                    return j;
                });
        }

        var ctx = Object.assign({}, cfg.relatorio);
        var aplicaveis = registrados.filter(function (d) {
            return d.eixos.some(function (e) { return ctx.eixos.indexOf(e) !== -1; });
        });

        for (var i = 0; i < aplicaveis.length; i++) {
            var def = aplicaveis[i];
            cfg.aoAtualizar(def.chave, { status: 'executando', rotulo: def.rotulo });
            try {
                var linhas = await def.executar(ctx);
                if (!Array.isArray(linhas)) throw new Error('o conector não devolveu uma lista de linhas');

                var gravadas = 0;
                var avisos = [];
                for (var j = 0; j < linhas.length; j += LOTE) {
                    var resp = await postar(cfg.urls.receber, {
                        conector: def.chave,
                        rotulo: def.rotulo,
                        dados: JSON.stringify(linhas.slice(j, j + LOTE)),
                        ultimo: (j + LOTE >= linhas.length) ? '1' : '0'
                    });
                    gravadas += resp.gravadas;
                    avisos = avisos.concat(resp.avisos || []);
                }
                if (linhas.length === 0) {
                    await postar(cfg.urls.fonte, { conector: def.chave, rotulo: def.rotulo, status: 'ok', mensagem: 'Nenhum dado retornado pela fonte.' });
                }
                cfg.aoAtualizar(def.chave, { status: 'ok', rotulo: def.rotulo, linhas: gravadas, avisos: avisos });
            } catch (e) {
                var msg = (e && e.message) ? e.message : String(e);
                try {
                    await postar(cfg.urls.fonte, { conector: def.chave, rotulo: def.rotulo, status: 'error', mensagem: msg });
                } catch (ignorado) { /* status fica só na tela */ }
                cfg.aoAtualizar(def.chave, { status: 'error', rotulo: def.rotulo, mensagem: msg });
            }
        }

        // Conectores do servidor (e-SUS e fontes sem acesso pelo navegador)
        if (cfg.urls.servidor) {
            try {
                var srv = await postar(cfg.urls.servidor, {});
                (srv.conectores || []).forEach(function (c) {
                    cfg.aoAtualizar(c.chave, { status: c.status, rotulo: c.rotulo, linhas: c.linhas, mensagem: c.mensagem, avisos: c.avisos });
                });
            } catch (e) {
                cfg.aoAtualizar('_servidor', { status: 'error', rotulo: 'Conectores do servidor', mensagem: e.message });
            }
        }

        return aplicaveis.length;
    };

    global.SXConectores = SXConectores;
})(window);
