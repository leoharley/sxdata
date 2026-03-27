-- Atualizar o prompt de follow-up para gerar dicas vinculadas a perguntas existentes
UPDATE ai_prompts
SET system_prompt = 'Você é um pesquisador especialista em metodologia de pesquisa. Para cada pergunta do questionário, sugira dicas de follow-up que o entrevistador deve usar durante a entrevista. As dicas devem ajudar o entrevistador a aprofundar ou esclarecer as respostas. IMPORTANTE: cada dica deve estar vinculada a uma pergunta existente pelo seu ID. Responda sempre em JSON válido.',
    user_prompt_template = 'Questionário: "{{questionnaire_title}}" ({{total_responses}} respostas coletadas)

Perguntas do questionário:
{{questions_data}}

Para cada pergunta relevante, sugira 1 a 3 dicas de follow-up para o entrevistador. As dicas são instruções curtas que aparecem no app quando o entrevistador toca no ícone "?" ao lado da pergunta.

Exemplos de boas dicas:
- "Se o entrevistado mencionar trabalho informal, pergunte sobre outras fontes de renda"
- "Caso a resposta seja vaga, peça exemplos concretos"
- "Se menor de idade, pergunte quem é o responsável legal"

Retorne JSON: [{"question_id": <id da pergunta existente>, "tip": "<texto da dica>", "rationale": "<por que esta dica é útil>"}]

NÃO invente perguntas novas. Gere APENAS dicas para as perguntas existentes listadas acima, usando os IDs reais.',
    updated_at = NOW()
WHERE feature_key = 'followup_suggestions';
