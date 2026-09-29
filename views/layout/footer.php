    </main>
    <footer class="app-footer"><span>© <?= date('Y') ?> DPPMS</span><span>Internal IT Development Team</span></footer>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>window.DPPMS = {
    csrfToken: <?= json_encode(csrf_token()) ?>,
    canManageUsers: <?= json_encode(($user['role'] ?? '') === 'SYSTEM ADMINISTRATOR') ?>,
    canManageProjects: <?= json_encode(in_array(($user['role'] ?? ''), PROJECT_MANAGER_ROLES, true)) ?>
};</script>
<script src="assets/js/app.js"></script>
<?php if (in_array(($currentPage ?? ''), ['users.php', 'projects.php'], true)): ?>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<?php endif; ?>
<?php if (($currentPage ?? '') === 'users.php'): ?><script src="assets/js/users.js"></script><?php endif; ?>
<?php if (($currentPage ?? '') === 'projects.php'): ?><script src="assets/js/projects.js"></script><?php endif; ?>
</body>
</html>
