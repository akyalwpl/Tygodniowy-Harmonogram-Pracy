<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tygodniowy Harmonogram Pracy</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php
        $conn = mysqli_connect("localhost", "root", "", "harmonogram");
        if (!$conn) {
            die("Connection failed: " . mysqli_connect_error());
        }
    ?>
    <header>
        <h1>Tygodniowy Harmonogram Pracy</h1>
    </header>

    <div class="filter-container">
        <form method="POST" action="">
            <label for="filter_date">Filtruj po dacie:</label>
            <input type="date" id="filter_date" name="filter_date" value="<?php echo isset($_POST['filter_date']) ? $_POST['filter_date'] : ''; ?>">
            <button type="submit" class="filter-btn">Filtruj</button>
            <a href="index.php" class="reset-btn">Resetuj</a>
        </form>
    </div>

    <div class="table-container">
        <table>
            <tr><th>Imię</th><th>Nazwisko</th><th>Godzina Rozpoczęcia</th><th>Godzina Zakończenia</th><th>Data</th><th>Projekt</th></tr>
            <?php
                $sql = "SELECT p.imie, p.nazwisko, h.godzinaroz, h.godzinazak, h.data, pr.nazwa AS projekt
                        FROM harmonogram h
                        JOIN pracownicy p ON h.idpracownika = p.idpracownika
                        JOIN projekty pr ON h.idprojekt = pr.idprojekt";

                if (isset($_POST['filter_date']) && !empty($_POST['filter_date'])) {
                    $filter_date = mysqli_real_escape_string($conn, $_POST['filter_date']);
                    $sql .= " WHERE h.data = '$filter_date'";
                }

                $sql .= " ORDER BY h.data DESC";
                $result = mysqli_query($conn, $sql);

                if (mysqli_num_rows($result) > 0) {
                    while($row = mysqli_fetch_assoc($result)) {
                        echo "<tr><td>" . $row["imie"] . "</td><td>" . $row["nazwisko"] . "</td><td>" . $row["godzinaroz"] . "</td><td>" . $row["godzinazak"] . "</td><td>" . $row["data"] . "</td><td>" . $row["projekt"] . "</td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>Brak danych w harmonogramie</td></tr>";
                }
                mysqli_close($conn);
            ?>
        </table>
    </div>


</body>
</html>