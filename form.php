<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Анкета - Задание 5</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Регистрационная анкета</h1>
    
    <!-- Кнопки входа/выхода (показываются только когда форма пуста или пользователь авторизован) -->
    <?php if (!$is_authenticated && empty($values['fio'])): ?>
        <div style="text-align: right; margin-bottom: 20px;">
            <a href="login.php" class="auth-btn auth-btn-login">Войти для редактирования</a>
        </div>
    <?php endif; ?>
    
    <?php if ($is_authenticated): ?>
        <div style="text-align: right; margin-bottom: 20px;">
            <a href="logout.php" class="auth-btn auth-btn-logout">Выйти</a>
        </div>
    <?php endif; ?>

    <!-- Вывод сообщений (ошибки, успех, логин/пароль) -->
    <?php if (!empty($messages)): ?>
        <?php foreach ($messages as $msg): ?>
            <?php echo $msg; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Форма отправляется на тот же URL (index.php) методом POST -->
    <form action="" method="POST">
        
        <!-- ПОЛЕ "ФИО" -->
        <div class="form-group">
            <label class="required">ФИО</label>
            <!-- value - подставляем сохранённое значение из Cookies или БД -->
            <!-- class="error" - если есть ошибка, поле подсвечивается красным -->
            <!-- htmlspecialchars() - защита от XSS атак -->
            <input type="text" name="fio" 
                   value="<?php echo htmlspecialchars($values['fio'] ?? ''); ?>"
                   class="<?php echo ($errors['fio'] ?? false) ? 'error' : ''; ?>">
        </div>

        <!-- ПОЛЕ "Телефон" -->
        <div class="form-group">
            <label class="required">Телефон</label>
            <input type="tel" name="phone" 
                   value="<?php echo htmlspecialchars($values['phone'] ?? ''); ?>"
                   class="<?php echo ($errors['phone'] ?? false) ? 'error' : ''; ?>">
        </div>

        <!-- ПОЛЕ "Email" -->
        <div class="form-group">
            <label class="required">Email</label>
            <input type="email" name="email" 
                   value="<?php echo htmlspecialchars($values['email'] ?? ''); ?>"
                   class="<?php echo ($errors['email'] ?? false) ? 'error' : ''; ?>">
        </div>

        <!-- ПОЛЕ "Дата рождения" -->
        <div class="form-group">
            <label class="required">Дата рождения</label>
            <input type="date" name="birthdate" 
                   value="<?php echo htmlspecialchars($values['birthdate'] ?? ''); ?>"
                   class="<?php echo ($errors['birthdate'] ?? false) ? 'error' : ''; ?>">
        </div>

        <!-- ПОЛЕ "Пол" (радиокнопки) -->
        <div class="form-group">
            <label class="required">Пол</label>
            <div class="radio-group">
                <label>
                    <input type="radio" name="gender" value="male" 
                        <?php echo (($values['gender'] ?? '') == 'male') ? 'checked' : ''; ?>> 
                    Мужской
                </label>
                <label>
                    <input type="radio" name="gender" value="female" 
                        <?php echo (($values['gender'] ?? '') == 'female') ? 'checked' : ''; ?>> 
                    Женский
                </label>
            </div>
            <?php if ($errors['gender'] ?? false): ?>
                <small style="color:red;">Выберите пол</small>
            <?php endif; ?>
        </div>

        <!-- ПОЛЕ "Любимый язык программирования" (множественный выбор) -->
        <div class="form-group">
            <label class="required">Любимый язык программирования</label>
            <select name="langs[]" multiple size="6" 
                    class="<?php echo ($errors['langs'] ?? false) ? 'error' : ''; ?>">
                <?php 
                // Список всех языков и их отображаемых названий
                $lang_list = ['pascal', 'c', 'c++', 'javascript', 'php', 'python', 
                              'java', 'haskell', 'clojure', 'prolog', 'scala', 'go'];
                $lang_display = [
                    'pascal' => 'Pascal', 'c' => 'C', 'c++' => 'C++',
                    'javascript' => 'JavaScript', 'php' => 'PHP', 'python' => 'Python',
                    'java' => 'Java', 'haskell' => 'Haskell', 'clojure' => 'Clojure',
                    'prolog' => 'Prolog', 'scala' => 'Scala', 'go' => 'Go'
                ];
                $selected_langs = $values['langs'] ?? [];
                foreach ($lang_list as $lang): 
                    $selected = in_array($lang, $selected_langs) ? 'selected' : '';
                ?>
                    <option value="<?php echo $lang; ?>" <?php echo $selected; ?>>
                        <?php echo $lang_display[$lang]; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small>Зажмите Ctrl (Cmd) для выбора нескольких</small>
        </div>

        <!-- ПОЛЕ "Биография" (многострочное) -->
        <div class="form-group">
            <label>Биография</label>
            <textarea name="bio" rows="5" 
                class="<?php echo ($errors['bio'] ?? false) ? 'error' : ''; ?>"><?php 
                echo htmlspecialchars($values['bio'] ?? ''); 
            ?></textarea>
        </div>

        <!-- ЧЕКБОКС "Согласие с контрактом" -->
        <div class="form-group checkbox-group">
            <label>
                <input type="checkbox" name="contract" 
                    <?php echo (($values['contract'] ?? '') == 'on') ? 'checked' : ''; ?>>
                Я ознакомлен с контрактом
            </label>
            <?php if ($errors['contract'] ?? false): ?>
                <div><small style="color:red;">Необходимо подтвердить ознакомление</small></div>
            <?php endif; ?>
        </div>

        <!-- КНОПКА ОТПРАВКИ -->
        <button type="submit">Сохранить</button>
        
    </form>
</div>
</body>
</html>
