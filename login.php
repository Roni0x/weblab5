<?php
/**
 * Файл login.php для неавторизованного пользователя выводит форму логина.
 * При отправке формы проверяет логин/пароль и создает сессию,
 * записывает в нее логин и id пользователя.
 * После авторизации пользователь перенаправляется на главную страницу
 * для изменения ранее введенных данных.
 */

// Отправляем браузеру правильную кодировку UTF-8
header('Content-Type: text/html; charset=UTF-8');

// Настройки подключения к базе данных
$db_host = 'localhost';
$db_name = 'u82465';
$db_user = 'u82465';
$db_pass = '3772684';

// Начинаем сессию (или продолжаем существующую)
session_start();

// Если пользователь уже авторизован - перенаправляем на форму (не нужно снова логиниться)
if (!empty($_SESSION['login'])) {
    header('Location: index.php');
    exit();
}

// ===== ОБРАБОТКА GET-ЗАПРОСА (показ формы входа) =====
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Проверяем, есть ли параметр error в URL (пришел после неудачной попытки входа)
    $error_message = '';
    if (!empty($_GET['error'])) {
        $error_message = '<div class="error-block">Неверный логин или пароль</div>';
    }
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Вход в систему</title>
        <link rel="stylesheet" href="style.css">
        <style>
            .login-container {
                max-width: 400px;
                margin: 100px auto;
                background: white;
                padding: 30px;
                border-radius: 12px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .login-container h1 {
                text-align: center;
                color: #1a73e8;
                margin-bottom: 30px;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <h1 style="color: #2d6a4f;">Вход для редактирования</h1>
            
            <!-- Показываем сообщение об ошибке, если оно есть -->
            <?php echo $error_message; ?>
            
            <!-- Форма входа отправляется методом POST на ту же страницу -->
            <form action="" method="post">
                <div class="form-group">
                    <label>Логин</label>
                    <input type="text" name="login" required>
                </div>
                <div class="form-group">
                    <label>Пароль</label>
                    <input type="password" name="pass" required>
                </div>
                <button type="submit">Войти</button>
            </form>
            <p style="text-align: center; margin-top: 20px;">
                <a href="index.php">Вернуться к форме</a>
            </p>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// ===== ОБРАБОТКА POST-ЗАПРОСА (проверка логина и пароля) =====
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Получаем логин и пароль из формы, удаляем лишние пробелы
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['pass'] ?? '');
    
    // Если поля пустые - сразу ошибка
    if (empty($login) || empty($password)) {
        header('Location: login.php?error=1');
        exit();
    }
    
    try {
        // Подключаемся к базе данных
        $pdo = new PDO(
            "mysql:host=$db_host;dbname=$db_name;charset=utf8",
            $db_user,
            $db_pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Ищем пользователя с таким логином в таблице users
        $stmt = $pdo->prepare("SELECT id, login, password_hash FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();
        
        // Проверяем, найден ли пользователь и совпадает ли пароль с хешем в БД
        // password_verify() сравнивает введенный пароль с хешем (безопасно)
        if ($user && password_verify($password, $user['password_hash'])) {
            // Пароль верный! Сохраняем данные в сессию
            $_SESSION['login'] = $user['login'];   // Запоминаем логин
            $_SESSION['uid'] = $user['id'];        // Запоминаем ID пользователя
            $_SESSION['user_id'] = $user['id'];    // Дополнительно для удобства
            
            // Перенаправляем на форму для редактирования данных
            header('Location: index.php');
            exit();
        } else {
            // Неверный логин или пароль
            header('Location: login.php?error=1');
            exit();
        }
        
    } catch (PDOException $e) {
        // Ошибка базы данных - логируем и показываем общую ошибку
        error_log("DB Error in login: " . $e->getMessage());
        header('Location: login.php?error=1');
        exit();
    }
}
?>
