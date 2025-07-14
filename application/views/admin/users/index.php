<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Usuários do Sistema</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="fas fa-plus me-2"></i>
                Novo Usuário
            </button>
        </div>
    </div>
</div>

<!-- Estatísticas dos Usuários -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(45deg, #007bff, #0056b3);">
                <i class="fas fa-users"></i>
            </div>
            <h3 class="stat-number"><?= $stats['total'] ?? 0 ?></h3>
            <p class="stat-label">Total de Usuários</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(45deg, #8fae5d, #a8c46a);">
                <i class="fas fa-user-check"></i>
            </div>
            <h3 class="stat-number"><?= $stats['aplicadores'] ?? 0 ?></h3>
            <p class="stat-label">Aplicadores</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(45deg, #ffc107, #e0a800);">
                <i class="fas fa-user-tie"></i>
            </div>
            <h3 class="stat-number"><?= $stats['supervisores'] ?? 0 ?></h3>
            <p class="stat-label">Supervisores</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(45deg, #dc3545, #bd2130);">
                <i class="fas fa-user-cog"></i>
            </div>
            <h3 class="stat-number"><?= $stats['administradores'] ?? 0 ?></h3>
            <p class="stat-label">Administradores</p>
        </div>
    </div>
</div>

<!-- Tabela de Usuários -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table data-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Usuário</th>
                        <th>Email</th>
                        <th>Função</th>
                        <th>Status</th>
                        <th>Último Login</th>
                        <th>Data Criação</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle me-3">
                                    <?= strtoupper(substr($user->full_name, 0, 2)) ?>
                                </div>
                                <strong><?= $user->full_name ?></strong>
                            </div>
                        </td>
                        <td><code><?= $user->username ?></code></td>
                        <td><?= $user->email ?></td>
                        <td>
                            <?php 
                            $role_colors = [
                                'administrador' => 'danger',
                                'supervisor' => 'warning',
                                'aplicador' => 'success'
                            ];
                            $color = $role_colors[$user->role] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?= $color ?>"><?= ucfirst($user->role) ?></span>
                        </td>
                        <td>
                            <?php if ($user->is_active): ?>
                                <span class="badge bg-success">Ativo</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (isset($user->last_login) && $user->last_login): ?>
                                <small><?= date('d/m/Y H:i', strtotime($user->last_login)) ?></small>
                            <?php else: ?>
                                <small class="text-muted">Nunca</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small><?= date('d/m/Y', strtotime($user->created_at)) ?></small>
                        </td>
                        <td class="text-center">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="editUser(<?= $user->id ?>)" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-warning" 
                                        onclick="resetPassword(<?= $user->id ?>)" title="Redefinir Senha">
                                    <i class="fas fa-key"></i>
                                </button>
                                <?php if ($user->id != $this->session->userdata('admin_id')): ?>
                                <button type="button" class="btn btn-sm btn-outline-<?= $user->is_active ? 'danger' : 'success' ?>" 
                                        onclick="toggleUserStatus(<?= $user->id ?>, <?= $user->is_active ? 'false' : 'true' ?>)" 
                                        title="<?= $user->is_active ? 'Desativar' : 'Ativar' ?>">
                                    <i class="fas fa-<?= $user->is_active ? 'ban' : 'check' ?>"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Criar Usuário -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Criar Novo Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <?= form_open('users/create', ['id' => 'createUserForm']) ?>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="full_name" class="form-label">Nome Completo *</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" required>
                </div>
                
                <div class="mb-3">
                    <label for="username" class="form-label">Usuário *</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                    <div class="form-text">Deve ser único no sistema</div>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email *</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                
                <div class="mb-3">
                    <label for="role" class="form-label">Função *</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="">Selecione...</option>
                        <option value="aplicador">Aplicador</option>
                        <option value="supervisor">Supervisor</option>
                        <option value="administrador">Administrador</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Senha *</label>
                    <input type="password" class="form-control" id="password" name="password" required minlength="6">
                    <div class="form-text">Mínimo 6 caracteres</div>
                </div>
                
                <div class="mb-3">
                    <label for="password_confirm" class="form-label">Confirmar Senha *</label>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Criar Usuário</button>
            </div>
            <?= form_close() ?>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #8fae5d, #a8c46a);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 14px;
}
</style>

<script>
function editUser(userId) {
    // Implementar modal de edição
    alert('Funcionalidade de edição em desenvolvimento');
}

function resetPassword(userId) {
    if (confirm('Tem certeza que deseja redefinir a senha deste usuário?')) {
        window.location.href = '<?= base_url('users/reset_password/') ?>' + userId;
    }
}

function toggleUserStatus(userId, activate) {
    const action = activate === 'true' ? 'ativar' : 'desativar';
    if (confirm(`Tem certeza que deseja ${action} este usuário?`)) {
        window.location.href = `<?= base_url('users/toggle_status/') ?>${userId}/${activate}`;
    }
}

// Validação de senhas iguais
document.getElementById('createUserForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('password_confirm').value;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('As senhas não coincidem!');
        return false;
    }
});
</script>