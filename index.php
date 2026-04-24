<?php
/**
 * ЗАДАНИЕ 5
 * 
 * Добавлена функциональность:
 * - Автоматическая генерация логина и пароля при первой отправке формы
 * - Сохранение хеша пароля в БД (password_hash)
 * - Отображение логина/пароля пользователю после успешной отправки
 * - Вход по логину/паролю через сессию
 * - Загрузка и отображение ранее сохранённых данных авторизованного пользователя
 * - Возможность редактирования и перезаписи данных
 * - Cookies для неавторизованных пользователей
 */

// Отправляем браузеру правильную кодировку UTF-8, чтобы русские буквы отображались корректно
header('Content-Type: text/html; charset=UTF-8');

// ---- НАСТРОЙКИ ПОДКЛЮЧЕНИЯ К БАЗЕ ДАННЫХ ----
$db_host = 'localhost';      // Хост БД (обычно localhost)
$db_name = 'u82465';         // Имя базы данных (совпадает с логином)
$db_user = 'u82465';         // Пользователь БД (совпадает с логином)
$db_pass = '3772684';        // Пароль от БД

// Константы для генерации логина и пароля (определяем длину)
define('LOGIN_LENGTH', 8);     // Логин будет длиной 8 символов
define('PASSWORD_LENGTH', 10); // Пароль будет длиной 10 символов

/**
 * Генерирует случайную строку заданной длины
 * Используется для создания логина и пароля
 * 
 * @param int $length Длина генерируемой строки
 * @return string Случайная строка из цифр и букв
 */
function generate_random_string($length = 8) {
    // Набор символов: цифры + строчные буквы + заглавные буквы
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $random_string = '';
    // Цикл, который выполняется $length раз
    for ($i = 0; $i < $length; $i++) {
        // На каждой итерации добавляем случайный символ из набора
        $random_string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $random_string;
}

/**
 * Функция для безопасного получения POST-данных
 * Удаляет лишние пробелы и проверяет существование поля
 * 
 * @param string $key Имя поля в массиве $_POST
 * @param string $default Значение по умолчанию
 * @return string Очищенное значение или значение по умолчанию
 */
function get_post_param($key, $default = '') {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

// ========== РАБОТА С СЕССИЕЙ (ЗАПОМИНАЕМ АВТОРИЗОВАННОГО ПОЛЬЗОВАТЕЛЯ) ==========

// Переменные для хранения состояния авторизации
$session_started = false;        // Флаг: сессия запущена?
$is_authenticated = false;       // Флаг: пользователь авторизован?
$current_user_id = null;         // ID текущего пользователя
$current_user_login = null;      // Логин текущего пользователя

// Проверяем, есть ли у пользователя кука сессии (PHPSESSID)
if (!empty($_COOKIE[session_name()])) {
    // Запускаем сессию - загружаем данные с сервера по ID из куки
    session_start();
    $session_started = true;
    // Проверяем, есть ли в сессии сохраненный логин
    if (!empty($_SESSION['login'])) {
        $is_authenticated = true;                    // Пользователь авторизован
        $current_user_login = $_SESSION['login'];    // Получаем логин из сессии
        $current_user_id = $_SESSION['uid'];         // Получаем ID из сессии
    }
}

// ========== ОБРАБОТКА GET-ЗАПРОСА (ПОКАЗ ФОРМЫ) ==========
// Сюда попадаем, когда пользователь просто открывает страницу
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    
    // Массив для хранения сообщений пользователю (успех, ошибки)
    $messages = array();
    // Массив для хранения флагов ошибок (true - поле подсветить красным)
    $errors = array();
    // Массив для хранения значений полей (чем заполнить форму)
    $values = array();

    // ===== ПРОВЕРЯЕМ КУКУ С ПРИЗНАКОМ УСПЕШНОГО СОХРАНЕНИЯ =====
    if (!empty($_COOKIE['save_success'])) {
        // Добавляем зеленое сообщение об успехе
        $messages[] = '<div class="success">Данные успешно сохранены!</div>';
        // Удаляем куку, чтобы сообщение не появилось снова при обновлении
        setcookie('save_success', '', time() - 3600);
        
        // ===== ПОКАЗЫВАЕМ СГЕНЕРИРОВАННЫЕ ЛОГИН И ПАРОЛЬ (при первой отправке) =====
        if (!empty($_COOKIE['generated_login']) && !empty($_COOKIE['generated_pass'])) {
            // Формируем желтое сообщение с данными для входа
            $messages[] = sprintf(
                '<div class="success" style="background: #fff3cd; border-color: #ffeaa7; color: #856404;">
                    <strong>Ваши данные для входа (сохраните их!):</strong><br>
                    Логин: <strong>%s</strong><br>
                    Пароль: <strong>%s</strong><br>
                    <a href="login.php">Войти</a> для редактирования данных
                </div>',
                htmlspecialchars($_COOKIE['generated_login']),
                htmlspecialchars($_COOKIE['generated_pass'])
            );
            // Удаляем куки с логином и паролем (чтобы не показывать снова)
            setcookie('generated_login', '', time() - 3600);
            setcookie('generated_pass', '', time() - 3600);
        }
    }

    // ===== ЗАГРУЗКА ДАННЫХ ДЛЯ АВТОРИЗОВАННОГО ПОЛЬЗОВАТЕЛЯ (из БД) =====
    if ($is_authenticated && $current_user_id) {
        try {
            // Подключаемся к базе данных
            $pdo = new PDO(
                "mysql:host=$db_host;dbname=$db_name;charset=utf8",
                $db_user,
                $db_pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            // Загружаем данные пользователя из таблицы users
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$current_user_id]);
            $user_data = $stmt->fetch();
            
            if ($user_data) {
                // Заполняем массив значений для формы данными из БД
                $values['fio'] = $user_data['full_name'] ?? '';
                $values['phone'] = $user_data['phone'] ?? '';
                $values['email'] = $user_data['email'] ?? '';
                $values['birthdate'] = $user_data['birth_date'] ?? '';
                $values['gender'] = $user_data['gender'] ?? '';
                $values['bio'] = $user_data['bio'] ?? '';
                $values['contract'] = $user_data['agreed_to_terms'] ? 'on' : '';
                
                // Загружаем выбранные языки программирования пользователя
                $stmt_langs = $pdo->prepare("
                    SELECT l.name FROM languages l
                    JOIN user_languages ul ON l.id = ul.language_id
                    WHERE ul.user_id = ?
                ");
                $stmt_langs->execute([$current_user_id]);
                $langs_data = $stmt_langs->fetchAll();
                // Превращаем массив языков в плоский массив с названиями
                $values['langs'] = array_column($langs_data, 'name');
                
                // Добавляем сообщение об успешном входе
                $messages[] = '<div class="success" style="background: #d8f3dc; border-left: 5px solid #2d6a4f; color: #1b4332;">
                    Вы вошли как <strong>' . htmlspecialchars($current_user_login) . '</strong>. 
                    Можете редактировать и сохранять данные.
                </div>';
            }
        } catch (PDOException $e) {
            // Логируем ошибку, но не показываем пользователю
            error_log("DB Error loading user data: " . $e->getMessage());
        }
    }
    
    // ===== ЗАГРУЗКА ДАННЫХ ДЛЯ НЕАВТОРИЗОВАННОГО ПОЛЬЗОВАТЕЛЯ (из Cookies) =====
    // Это функциональность из Задания 4 - сохраняем значения между попытками
    if (!$is_authenticated) {
        // Список всех полей формы
        $fields = ['fio', 'phone', 'email', 'birthdate', 'gender', 'bio', 'contract'];
        foreach ($fields as $field) {
            // Проверяем, есть ли кука с ошибкой для этого поля
            $errors[$field] = !empty($_COOKIE[$field . '_error']);
            // Загружаем сохраненное значение поля (если есть)
            $values[$field] = isset($_COOKIE[$field . '_value']) ? $_COOKIE[$field . '_value'] : '';
            // Удаляем куку ошибки после прочтения (одноразовое сообщение)
            if ($errors[$field]) {
                setcookie($field . '_error', '', time() - 3600);
            }
        }
        
        // Отдельная обработка для языков (массив, а не строка)
        $errors['langs'] = !empty($_COOKIE['langs_error']);
        $values['langs'] = isset($_COOKIE['langs_value']) ? unserialize($_COOKIE['langs_value']) : [];
        if ($errors['langs']) {
            setcookie('langs_error', '', time() - 3600);
        }
    }

    // ===== ФОРМИРУЕМ ПОНЯТНЫЕ СООБЩЕНИЯ ОБ ОШИБКАХ =====
    $error_messages = [];
    if (!empty($errors['fio'])) $error_messages[] = 'Ошибка в поле ФИО (только буквы и пробелы, до 150 символов).';
    if (!empty($errors['phone'])) $error_messages[] = 'Ошибка в поле Телефон (допустимы цифры, +, -, пробелы).';
    if (!empty($errors['email'])) $error_messages[] = 'Ошибка в поле Email (неверный формат).';
    if (!empty($errors['birthdate'])) $error_messages[] = 'Ошибка в поле Дата рождения.';
    if (!empty($errors['gender'])) $error_messages[] = 'Ошибка в поле Пол (выберите значение).';
    if (!empty($errors['langs'])) $error_messages[] = 'Ошибка в поле Любимый язык (выберите из списка).';
    if (!empty($errors['bio'])) $error_messages[] = 'Ошибка в поле Биография (не более 1000 символов).';
    if (!empty($errors['contract'])) $error_messages[] = 'Необходимо согласие с контрактом.';

    // Если есть ошибки - добавляем блок с сообщениями вверху формы
    if (!empty($error_messages)) {
        $messages[] = '<div class="error-block"><strong>Исправьте ошибки:</strong><br>' 
                    . implode('<br>', $error_messages) . '</div>';
    }

    // Подключаем файл с HTML-формой (в нём будут доступны переменные $messages, $errors, $values, $is_authenticated)
    include('form.php');
    exit();  // Останавливаем выполнение скрипта, чтобы не пошел в POST-блок
}

// ========== ОБРАБОТКА POST-ЗАПРОСА (СОХРАНЕНИЕ ДАННЫХ) ==========
// Сюда попадаем, когда пользователь нажал кнопку "Сохранить"
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Флаг наличия ошибок валидации (изначально false - ошибок нет)
    $has_error = false;

    // ===== ВАЛИДАЦИЯ ПОЛЯ "ФИО" =====
    $fio = get_post_param('fio');
    // Проверка: не пустое и содержит только буквы, пробелы, дефис (от 1 до 150 символов)
    if (empty($fio) || !preg_match('/^[a-zA-Zа-яА-ЯёЁ\s\-]{1,150}$/u', $fio)) {
        // Сохраняем куку-ошибку на 24 часа (будет красная подсветка)
        setcookie('fio_error', '1', time() + 86400);
        $has_error = true;  // Поднимаем флаг, что есть ошибка
    }
    // Сохраняем значение поля в куку на 365 дней (даже если с ошибкой)
    setcookie('fio_value', $fio, time() + 365 * 86400);

    // ===== ВАЛИДАЦИЯ ПОЛЯ "Телефон" =====
    $phone = get_post_param('phone');
    // Регулярка: цифры, пробелы, дефисы, плюсы, скобки, длина 5-20 символов
    if (empty($phone) || !preg_match('/^[\d\s\-\+\(\)]{5,20}$/', $phone)) {
        setcookie('phone_error', '1', time() + 86400);
        $has_error = true;
    }
    setcookie('phone_value', $phone, time() + 365 * 86400);

    // ===== ВАЛИДАЦИЯ ПОЛЯ "Email" =====
    $email = get_post_param('email');
    // filter_var - встроенная проверка корректности email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setcookie('email_error', '1', time() + 86400);
        $has_error = true;
    }
    setcookie('email_value', $email, time() + 365 * 86400);

    // ===== ВАЛИДАЦИЯ ПОЛЯ "Дата рождения" =====
    $birthdate = get_post_param('birthdate');
    $date_check = date_create($birthdate);
    if (empty($birthdate) || !$date_check) {
        setcookie('birthdate_error', '1', time() + 86400);
        $has_error = true;
    }
    setcookie('birthdate_value', $birthdate, time() + 365 * 86400);

    // ===== ВАЛИДАЦИЯ ПОЛЯ "Пол" =====
    $gender = get_post_param('gender');
    // Проверяем, что значение строго 'male' или 'female'
    if (!in_array($gender, ['male', 'female'])) {
        setcookie('gender_error', '1', time() + 86400);
        $has_error = true;
    }
    setcookie('gender_value', $gender, time() + 365 * 86400);

    // ===== ВАЛИДАЦИЯ ПОЛЯ "Языки программирования" =====
    // Получаем массив выбранных языков (если не выбран ни один - пустой массив)
    $langs = isset($_POST['langs']) ? $_POST['langs'] : [];
    // Список разрешённых языков (защита от подделки)
    $allowed_langs = ['pascal', 'c', 'c++', 'javascript', 'php', 'python', 
                      'java', 'haskell', 'clojure', 'prolog', 'scala', 'go'];
    // Оставляем только те языки, которые есть в разрешенном списке
    $valid_langs = array_intersect($langs, $allowed_langs);
    // Если не выбран ни один разрешённый язык - ошибка
    if (empty($valid_langs)) {
        setcookie('langs_error', '1', time() + 86400);
        $has_error = true;
    }
    // Сохраняем выбранные языки в куку (serialize превращает массив в строку)
    setcookie('langs_value', serialize($valid_langs), time() + 365 * 86400);

    // ===== ВАЛИДАЦИЯ ПОЛЯ "Биография" =====
    $bio = get_post_param('bio');
    // Проверяем, что длина не больше 1000 символов
    if (strlen($bio) > 1000) {
        setcookie('bio_error', '1', time() + 86400);
        $has_error = true;
    }
    setcookie('bio_value', $bio, time() + 365 * 86400);

    // ===== ВАЛИДАЦИЯ ЧЕКБОКСА "Согласие с контрактом" =====
    $contract = get_post_param('contract');
    // Чекбокс передаёт значение 'on' если отмечен
    if ($contract !== 'on') {
        setcookie('contract_error', '1', time() + 86400);
        $has_error = true;
    }
    setcookie('contract_value', $contract, time() + 365 * 86400);

    // ===== ЕСЛИ ЕСТЬ ОШИБКИ - ПЕРЕНАПРАВЛЯЕМ ОБРАТНО НА ФОРМУ =====
    if ($has_error) {
        header('Location: index.php');
        exit();
    }

    // ===== СОХРАНЕНИЕ В БАЗУ ДАННЫХ (выполняется только если ошибок нет) =====
    try {
        // Подключаемся к базе данных через PDO
        $pdo = new PDO(
            "mysql:host=$db_host;dbname=$db_name;charset=utf8",
            $db_user,
            $db_pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,   // Включаем режим исключений
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC  // Результат в виде ассоциативного массива
            ]
        );

        // Определяем: авторизован пользователь или нет?
        // Если авторизован - будем обновлять существующую запись (UPDATE)
        // Если нет - будем создавать новую запись (INSERT)
        $is_update = ($is_authenticated && $current_user_id);
        
        if ($is_update) {
            // ===== РЕДАКТИРОВАНИЕ СУЩЕСТВУЮЩЕЙ ЗАПИСИ (авторизованный пользователь) =====
            $pdo->beginTransaction();
            
            // Обновляем данные в таблице users
            $stmt = $pdo->prepare("UPDATE users SET 
                full_name = ?, phone = ?, email = ?, birth_date = ?, 
                gender = ?, bio = ?, agreed_to_terms = ? 
                WHERE id = ?");
            $stmt->execute([$fio, $phone, $email, $birthdate, $gender, $bio, 1, $current_user_id]);
            
            // Удаляем старые связи с языками (чтобы вставить новые)
            $stmt_del = $pdo->prepare("DELETE FROM user_languages WHERE user_id = ?");
            $stmt_del->execute([$current_user_id]);
            
            // Получаем ID выбранных языков из таблицы languages
            $placeholders = implode(',', array_fill(0, count($valid_langs), '?'));
            $stmt_lang_ids = $pdo->prepare("SELECT id, name FROM languages WHERE name IN ($placeholders)");
            $stmt_lang_ids->execute($valid_langs);
            $lang_ids = $stmt_lang_ids->fetchAll();
            
            // Вставляем новые связи пользователь-язык
            $stmt_link = $pdo->prepare("INSERT INTO user_languages (user_id, language_id) VALUES (?, ?)");
            foreach ($lang_ids as $lang) {
                $stmt_link->execute([$current_user_id, $lang['id']]);
            }
            
            $pdo->commit();
            $user_id = $current_user_id;  // ID пользователя не меняется
            
        } else {
            // ===== НОВАЯ ЗАПИСЬ (неавторизованный пользователь) =====
            $pdo->beginTransaction();
            
            // Генерируем уникальный логин (проверяем, что такого еще нет в БД)
            do {
                $new_login = generate_random_string(LOGIN_LENGTH);
                $stmt_check = $pdo->prepare("SELECT id FROM users WHERE login = ?");
                $stmt_check->execute([$new_login]);
                $exists = $stmt_check->fetch();  // true если логин уже занят
            } while ($exists);  // Повторяем, пока не найдем свободный логин
            
            // Генерируем пароль и создаем его безопасный хеш
            $new_password = generate_random_string(PASSWORD_LENGTH);
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Вставляем новую запись в таблицу users
            $stmt = $pdo->prepare("INSERT INTO users 
                (login, password_hash, full_name, phone, email, birth_date, gender, bio, agreed_to_terms) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$new_login, $password_hash, $fio, $phone, $email, $birthdate, $gender, $bio, 1]);
            $user_id = $pdo->lastInsertId();  // Получаем ID новой записи
            
            // Получаем ID выбранных языков из таблицы languages
            $placeholders = implode(',', array_fill(0, count($valid_langs), '?'));
            $stmt_lang_ids = $pdo->prepare("SELECT id, name FROM languages WHERE name IN ($placeholders)");
            $stmt_lang_ids->execute($valid_langs);
            $lang_ids = $stmt_lang_ids->fetchAll();
            
            // Вставляем связи пользователь-язык
            $stmt_link = $pdo->prepare("INSERT INTO user_languages (user_id, language_id) VALUES (?, ?)");
            foreach ($lang_ids as $lang) {
                $stmt_link->execute([$user_id, $lang['id']]);
            }
            
            $pdo->commit();
            
            // Сохраняем сгенерированные логин и пароль в куки на 60 секунд
            // При следующем GET-запросе покажем их пользователю
            setcookie('generated_login', $new_login, time() + 60);
            setcookie('generated_pass', $new_password, time() + 60);
        }

        // ===== ОЧИСТКА КУК С ДАННЫМИ ФОРМЫ (только для неавторизованных) =====
        // Если пользователь не авторизован, удаляем временные куки со значениями
        // Так как данные уже сохранены в БД, они больше не нужны
        if (!$is_authenticated) {
            $fields = ['fio', 'phone', 'email', 'birthdate', 'gender', 'bio', 'contract'];
            foreach ($fields as $field) {
                setcookie($field . '_value', '', time() - 3600);
            }
            setcookie('langs_value', '', time() - 3600);
        }
        
        // Устанавливаем куку с признаком успешного сохранения (на 30 секунд)
        // При следующем GET-запросе покажем зеленое сообщение
        setcookie('save_success', '1', time() + 30);

        // Перенаправляем на GET-запрос (чтобы не было повторной отправки формы)
        header('Location: index.php');
        exit();

    } catch (PDOException $e) {
        // Если произошла ошибка - откатываем транзакцию (отменяем все изменения)
        if (isset($pdo)) $pdo->rollBack();
        // Записываем ошибку в лог сервера
        error_log("DB Error: " . $e->getMessage());
        
        // Показываем сообщение об ошибке пользователю
        $messages[] = '<div class="error-block">Ошибка базы данных: ' . $e->getMessage() . '</div>';
        include('form.php');
        exit();
    }
}
?>
