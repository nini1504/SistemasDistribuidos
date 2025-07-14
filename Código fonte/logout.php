<?php
session_start();
session_unset();
session_destroy();

header("Location: tipoUsuario.php"); // ou index.php, como preferir
exit();
