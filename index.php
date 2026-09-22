<?php
// Página inicial: manda para o painel (se logado) ou para o login.
require_once __DIR__ . '/includes/auth.php';
redirecionar(usuario_atual() ? 'painel.php' : 'login.php');
