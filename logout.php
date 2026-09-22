<?php
require_once __DIR__ . '/includes/auth.php';
fazer_logout();
session_start();
flash('sucesso', 'Você saiu do sistema.');
redirecionar('login.php');
