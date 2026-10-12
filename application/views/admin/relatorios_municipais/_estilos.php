<style>
    .rm-card { background: #fff; border-radius: .75rem; box-shadow: 0 2px 8px rgba(0,0,0,.07); padding: 1.25rem; margin-bottom: 1rem; }
    .rm-kpi { display: flex; align-items: center; gap: .9rem; }
    .rm-kpi-icone { width: 46px; height: 46px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
                    color: #fff; font-size: 1.1rem; flex-shrink: 0; }
    .rm-kpi-valor { font-size: 1.6rem; font-weight: 700; color: #23345F; line-height: 1.1; }
    .rm-kpi-rotulo { font-size: .85rem; color: #6c757d; }
    .rm-tabela td, .rm-tabela th { vertical-align: middle; }
    .rm-progresso { height: 6px; border-radius: 3px; background: #e9ecef; overflow: hidden; min-width: 90px; }
    .rm-progresso > div { height: 100%; border-radius: 3px; }
    .rm-passos { display: flex; gap: .5rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
    .rm-passo { flex: 1; min-width: 120px; text-align: center; font-size: .85rem; color: #6c757d; }
    .rm-passo .num { width: 30px; height: 30px; border-radius: 50%; border: 2px solid #ced4da; display: inline-flex;
                     align-items: center; justify-content: center; font-weight: 600; background: #fff; margin-bottom: .3rem; }
    .rm-passo.ativo { color: #5a7536; font-weight: 600; }
    .rm-passo.ativo .num, .rm-passo.feito .num { border-color: #8fae5d; background: #8fae5d; color: #fff; }
    .rm-grafico-box { position: relative; height: 280px; }
    .rm-fonte { font-size: .78rem; color: #6c757d; }
    .rm-vazio { height: 280px; display: flex; flex-direction: column; align-items: center; justify-content: center;
                color: #9aa4ae; background: #f8f9fa; border-radius: .5rem; text-align: center; padding: 1rem; }
</style>
