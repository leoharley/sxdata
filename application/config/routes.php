<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'dashboard';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// Admin routes
$route['admin'] = 'dashboard';
$route['admin/login'] = 'auth/login';
$route['admin/logout'] = 'auth/logout';
$route['admin/dashboard'] = 'dashboard';
$route['admin/questionnaires'] = 'questionnaires';
$route['admin/questionnaires/create'] = 'questionnaires/create';
$route['admin/questionnaires/edit/(:num)'] = 'questionnaires/edit/$1';
$route['admin/questionnaires/duplicate/(:num)'] = 'questionnaires/duplicate/$1';
$route['admin/responses'] = 'responses';
$route['admin/responses/view/(:num)'] = 'responses/view/$1';
$route['admin/responses/export'] = 'responses/export';
$route['admin/users'] = 'users';
$route['admin/reports'] = 'reports';
$route['admin/settings'] = 'settings';
$route['politica_de_privacidade'] = 'welcome/politica_de_privacidade';

// Rotas do Módulo de IA
$route['admin/ai'] = 'ai';
$route['admin/ai/settings'] = 'ai/settings';
$route['admin/ai/prompts'] = 'ai/prompts';
$route['admin/ai/logs'] = 'ai/logs';
$route['admin/ai/transcriptions'] = 'ai/transcriptions';
$route['admin/ai/inconsistencies'] = 'ai/inconsistencies';
$route['admin/ai/corrections'] = 'ai/corrections';
$route['admin/ai/smart_fill'] = 'ai/smart_fill';
$route['ai/approve_suggestion'] = 'ai/approve_suggestion';
$route['ai/reject_suggestion'] = 'ai/reject_suggestion';
$route['admin/ai/reformulations'] = 'ai/reformulations';
$route['admin/ai/adaptive'] = 'ai/adaptive';
$route['admin/ai/followup'] = 'ai/followup';
$route['admin/ai/analysis'] = 'ai/analysis';
$route['admin/ai/view_analysis/(:num)'] = 'ai/view_analysis/$1';
$route['admin/ai/charts'] = 'ai/charts';
$route['admin/ai/reports'] = 'ai/reports';
$route['admin/ai/view_report/(:num)'] = 'ai/view_report/$1';

// API routes
$route['api/auth/login'] = 'api/auth/login';
$route['api/auth/verify'] = 'api/auth/verify';
$route['api/questionnaires'] = 'api/questionnaires/index';
$route['api/forms/submit'] = 'api/forms/submit';
$route['api/photos/upload'] = 'api/photos/upload';

// Rotas para API de Estatísticas
$route['api/stats/user/(:num)'] = 'api/stats/user/$1';
$route['api/stats/user'] = 'api/stats/user';
$route['api/stats/overview'] = 'api/stats/overview';

// Novas rotas para Supervisores/Administradores
$route['api/stats/applicators'] = 'api/stats/applicators';
$route['api/stats/locations'] = 'api/stats/locations';

$route['api/stats/history'] = 'api/stats/history';
$route['api/stats/history/(:num)'] = 'api/stats/history/$1';
$route['api/stats/history/(:num)/summary'] = 'api/stats/history_summary/$1';
$route['api/stats/history/summary'] = 'api/stats/history_summary';

$route['api/stats/questionnaires-analysis'] = 'api/stats/questionnaires_analysis';
$route['api/stats/questionnaire-analysis/(:num)'] = 'api/stats/questionnaire_analysis/$1';
$route['api/stats/questions-analysis'] = 'api/stats/questions_analysis';
$route['api/stats/applicators_app'] = 'api/stats/applicators_app';


$route['api/questionnaires/(:num)/questions'] = 'api/responses/questionnaire_questions/$1';
$route['api/responses/raw-data'] = 'api/responses/raw_data';
$route['api/responses/count'] = 'api/responses/count_records';
$route['api/responses/validate-export'] = 'api/responses/validate_export';
$route['api/responses/export-preview'] = 'api/responses/export_preview';
$route['api/responses/export-statistics'] = 'api/responses/export_statistics';
$route['api/responses/log-export'] = 'api/responses/log_export';
$route['api/responses/export-limits'] = 'api/responses/export_limits';
$route['api/responses/export-history'] = 'api/responses/export_history';


$route['responses/export_raw_data'] = 'responses/export_raw_data';

// Rotas adicionais úteis para a funcionalidade
$route['responses/preview_export'] = 'responses/preview_export';
$route['responses/validate_export'] = 'responses/validate_export';
$route['responses/export_status/(:num)'] = 'responses/export_status/$1';

// Rotas para AJAX calls
$route['api/responses/count'] = 'responses/ajax_count_responses';
$route['api/responses/preview'] = 'responses/ajax_preview_export';

$route['api/health'] = 'api/system/health';

// API de IA (preparação para app móvel)
$route['api/ai/status'] = 'api/ai/status';
$route['api/ai/transcribe'] = 'api/ai/transcribe';
$route['api/ai/check-inconsistencies'] = 'api/ai/check_inconsistencies';
$route['api/ai/suggest-fill'] = 'api/ai/suggest_fill';
$route['api/ai/standardize'] = 'api/ai/standardize';
$route['api/ai/smart-suggestions'] = 'api/ai/smart_suggestions';
$route['api/ai/reformulate-questions'] = 'api/ai/reformulate_questions';
$route['api/ai/adaptive-next'] = 'api/ai/adaptive_next';
$route['api/ai/follow-up'] = 'api/ai/follow_up';
$route['api/ai/generate-report'] = 'api/ai/generate_report';
$route['api/ai/audit-log'] = 'api/ai/audit_log';
$route['api/ai/audit-log/batch'] = 'api/ai/audit_log_batch';