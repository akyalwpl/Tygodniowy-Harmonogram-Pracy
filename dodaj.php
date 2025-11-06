<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktualizuj Harmonogram</title>
    <link rel="stylesheet" href="styl.css">
</head>
<body>
    <?php
        $conn = mysqli_connect("localhost", "root", "", "harmonogram");
        if (!$conn) {
            die("Connection failed: " . mysqli_connect_error());
        }

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['add_schedule'])) {
                $idpracownika = mysqli_real_escape_string($conn, $_POST['idpracownika']);
                $godzinaroz = mysqli_real_escape_string($conn, $_POST['godzinaroz']);
                $godzinazak = mysqli_real_escape_string($conn, $_POST['godzinazak']);
                $idprojekt = mysqli_real_escape_string($conn, $_POST['idprojekt']);
                $data = mysqli_real_escape_string($conn, $_POST['data']);

                // Check for time conflicts
                $conflict_sql = "SELECT godzinaroz, godzinazak FROM harmonogram
                                WHERE idpracownika = '$idpracownika' AND data = '$data'
                                AND (
                                    ('$godzinaroz' >= godzinaroz AND '$godzinaroz' < godzinazak) OR
                                    ('$godzinazak' > godzinaroz AND '$godzinazak' <= godzinazak) OR
                                    ('$godzinaroz' <= godzinaroz AND '$godzinazak' >= godzinazak)
                                )";

                $conflict_result = mysqli_query($conn, $conflict_sql);

                if (mysqli_num_rows($conflict_result) > 0) {
                    echo "<script>alert('Ten pracownik ma już wpisany harmonogram tego dnia!');</script>";
                } else {
                    $sql = "INSERT INTO harmonogram (idpracownika, godzinaroz, godzinazak, data, idprojekt) VALUES ('$idpracownika', '$godzinaroz', '$godzinazak', '$data', '$idprojekt')";

                    if (mysqli_query($conn, $sql)) {
                        // Get employee email
                        $email_sql = "SELECT email FROM pracownicy WHERE idpracownika = '$idpracownika'";
                        $email_result = mysqli_query($conn, $email_sql);
                        $employee = mysqli_fetch_assoc($email_result);
                        $employee_email = $employee['email'];

                        // Send email using Resend API
                        $api_key = 're_S2DCmKcn_Lag28zJzZ91Uppgzvy5uNu7Q';
                        $url = 'https://api.resend.com/emails';

                        $email_data = array(
                            'from' => 'onboarding@resend.dev',
                            'to' => array($employee_email),
                            'subject' => 'Harmonogram',
                            'html' => 'Został wpisany harmonogram dnia ' . $data . ' godziny ' . $godzinaroz . ' do ' . $godzinazak . ' w firmie XXX'
                        );

                        $ch = curl_init($url);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($email_data));
                        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                            'Authorization: Bearer ' . $api_key,
                            'Content-Type: application/json'
                        ));

                        $response = curl_exec($ch);
                        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);

                        if ($http_code == 200) {
                            echo "<script>alert('Harmonogram został dodany pomyślnie! Email został wysłany.'); window.location.href='dodaj.php';</script>";
                        } else {
                            $error_message = json_decode($response, true);
                            $error_text = isset($error_message['message']) ? $error_message['message'] : 'Nieznany błąd';
                            echo "<script>alert('Harmonogram został dodany pomyślnie, ale wystąpił błąd podczas wysyłania emaila: " . addslashes($error_text) . "');</script>";
                        }
                    } else {
                        echo "<script>alert('Błąd podczas dodawania harmonogramu: " . mysqli_error($conn) . "');</script>";
                    }
                }
            } elseif (isset($_POST['add_project'])) {
                $nazwa = mysqli_real_escape_string($conn, $_POST['nazwa']);
                $opis = mysqli_real_escape_string($conn, $_POST['opis']);

                $sql = "INSERT INTO projekty (nazwa, opis) VALUES ('$nazwa', '$opis')";

                if (mysqli_query($conn, $sql)) {
                    echo "<script>alert('Projekt został dodany pomyślnie!'); window.location.href='dodaj.php';</script>";
                } else {
                    echo "<script>alert('Błąd podczas dodawania projektu: " . mysqli_error($conn) . "');</script>";
                }
            } elseif (isset($_POST['add_employee'])) {
                $imie = mysqli_real_escape_string($conn, $_POST['imie']);
                $nazwisko = mysqli_real_escape_string($conn, $_POST['nazwisko']);
                $email = mysqli_real_escape_string($conn, $_POST['email']);

                $sql = "INSERT INTO pracownicy (imie, nazwisko, email) VALUES ('$imie', '$nazwisko', '$email')";

                if (mysqli_query($conn, $sql)) {
                    echo "<script>alert('Pracownik został dodany pomyślnie!'); window.location.href='dodaj.php';</script>";
                } else {
                    echo "<script>alert('Błąd podczas dodawania pracownika: " . mysqli_error($conn) . "');</script>";
                }
            }
        }

        // Fetch employees
        $sql_employees = "SELECT idpracownika, imie, nazwisko FROM pracownicy ORDER BY nazwisko";
        $result_employees = mysqli_query($conn, $sql_employees);

        // Fetch projects
        $sql_projects = "SELECT idprojekt, nazwa, opis FROM projekty ORDER BY nazwa";
        $result_projects = mysqli_query($conn, $sql_projects);
    ?>
    <header>
        <h1>Wpisz dane</h1>
        <nav>
            <a href="index.php" class="nav-link">Powrot</a>
        </nav>
    </header>

    <style>
        .forms-container {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            padding: 0 2rem;
            justify-content: center;
        }

        .form-column {
            flex: 1;
            min-width: 300px;
            max-width: 30%;
        }

        .schedule-form {
            display: flex;
            gap: 2rem;
        }

        .schedule-column {
            flex: 1;
        }

        .project-employee-forms {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .employee-selection-container {
            flex: 1;
        }

        .custom-input, .custom-select, .custom-textarea {
            padding: 0.75rem 1rem;
            border: 1px solid #cccccc;
            border-radius: 8px;
            background-color: #ffffff;
            font-size: 1rem;
            color: #333333;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            width: 100%;
            box-sizing: border-box;
        }

        .custom-input:focus, .custom-select:focus, .custom-textarea:focus {
            outline: none;
            border-color: #000000;
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
        }

        .custom-input:hover, .custom-select:hover, .custom-textarea:hover {
            border-color: #000000;
        }

        .custom-textarea {
            resize: vertical;
            min-height: 100px;
        }

        .custom-select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23000000' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 1rem;
            padding-right: 2.5rem;
        }
        
        form select,
        form input {
            margin-bottom: 30px;
        }

        form {
            min-height: 510px;
        }
        @media (max-width: 1200px) {
            .forms-container {
                flex-direction: column;
                gap: 1rem;
            }

            .form-column {
                max-width: 100%;
            }

            .schedule-form {
                flex-direction: column;
                gap: 1rem;
            }
        }

        @media (max-width: 768px) {
            .project-employee-forms {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>

    <div class="forms-container">
        <div class="form-column">
            <div class="employee-selection-container">
                <form method="POST" action="">
                    <h3>Wpisz Harmonogram</h3>
                    <div class="schedule-form">
                        <div class="schedule-column">
                            <label for="idpracownika">Wybierz pracownika:</label>
                            <select id="idpracownika" name="idpracownika" class="custom-select" required>
                                <option value="">-- Wybierz pracownika --</option>
                                <?php
                                    if (mysqli_num_rows($result_employees) > 0) {
                                        while($row = mysqli_fetch_assoc($result_employees)) {
                                            echo "<option value='" . $row['idpracownika'] . "'>" . $row['imie'] . " " . $row['nazwisko'] . "</option>";
                                        }
                                    }
                                ?>
                            </select>

                            <label for="godzinaroz">Godzina rozpoczęcia:</label>
                            <input type="time" id="godzinaroz" name="godzinaroz" class="custom-input" required>

                            <label for="godzinazak">Godzina zakończenia:</label>
                            <input type="time" id="godzinazak" name="godzinazak" class="custom-input" required>
                        </div>

                        <div class="schedule-column">
                            <label for="idprojekt">Wybierz projekt:</label>
                            <select id="idprojekt" name="idprojekt" class="custom-select" required>
                                <option value="">-- Wybierz projekt --</option>
                                <?php
                                    if (mysqli_num_rows($result_projects) > 0) {
                                        while($row = mysqli_fetch_assoc($result_projects)) {
                                            echo "<option value='" . $row['idprojekt'] . "'>" . $row['nazwa'] . " - " . $row['opis'] . "</option>";
                                        }
                                    }
                                ?>
                            </select>

                            <label for="data">Data:</label>
                            <input type="date" id="data" name="data" class="custom-input" required value="2025-01-01">

                            <button type="submit" name="add_schedule" class="filter-btn" style="padding-right: 44px; padding-left: 44px;">Dodaj harmonogram</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="form-column">
            <div class="employee-selection-container">
                <form method="POST" action="">
                    <h3>Dodaj projekt</h3>
                    <label for="nazwa">Nazwa projektu:</label>
                    <input type="text" id="nazwa" name="nazwa" class="custom-input" required>

                    <label for="opis">Opis projektu:</label>
                    <textarea id="opis" name="opis" class="custom-textarea" required></textarea>

                    <button type="submit" name="add_project" class="filter-btn">Dodaj projekt</button>
                </form>
            </div>
        </div>

        <div class="form-column">
            <div class="employee-selection-container">
                <form method="POST" action="">
                    <h3>Dodaj pracownika</h3>
                    <label for="imie">Imię:</label>
                    <input type="text" id="imie" name="imie" class="custom-input" required>

                    <label for="nazwisko">Nazwisko:</label>
                    <input type="text" id="nazwisko" name="nazwisko" class="custom-input" required>

                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" class="custom-input" required>

                    <button type="submit" name="add_employee" class="filter-btn">Dodaj pracownika</button>
                </form>
            </div>
        </div>
    </div>

    <?php mysqli_close($conn); ?>
</body>
</html>