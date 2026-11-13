<?php
require_once '../config/config.php';

// Destruir sessão
session_destroy();

// Redirecionar para home
redirect('/');
