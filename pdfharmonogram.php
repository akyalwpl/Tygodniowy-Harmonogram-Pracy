<?php
require('fpdf.php');

$conn = mysqli_connect("localhost", "root", "", "harmonogram");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$sql_employees = "SELECT idpracownika, imie, nazwisko FROM pracownicy ORDER BY nazwisko";
$result_employees = mysqli_query($conn, $sql_employees);

if (!isset($_POST['generate_schedule']) || !isset($_POST['employee_id']) || empty($_POST['employee_id'])) {
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Harmonogram Pracownika - PDF</title>
    <link rel="stylesheet" href="styl.css">
</head>
<body>
    <header>
        <h1>Harmonogram Pracownika - PDF</h1>
        <nav>
            <a href="index.php" class="nav-link">Powrót</a>
        </nav>
    </header>

    <div class="employee-selection-container">
        <form method="POST" action="">
            <label for="employee_id">Wybierz pracownika:</label>
            <select id="employee_id" name="employee_id" required>
                <option value="">-- Wybierz pracownika --</option>
                <?php
                    if (mysqli_num_rows($result_employees) > 0) {
                        while($row = mysqli_fetch_assoc($result_employees)) {
                            $selected = (isset($_POST['employee_id']) && $_POST['employee_id'] == $row['idpracownika']) ? 'selected' : '';
                            echo "<option value='" . $row['idpracownika'] . "' $selected>" . $row['imie'] . " " . $row['nazwisko'] . "</option>";
                        }
                    }
                ?>
            </select>
            <button type="submit" name="generate_schedule" class="filter-btn">Generuj harmonogram</button>
        </form>
    </div>
</body>
</html>
<?php
} else {
    $employee_id = mysqli_real_escape_string($conn, $_POST['employee_id']);

    $sql_employee = "SELECT imie, nazwisko FROM pracownicy WHERE idpracownika = '$employee_id'";
    $result_employee = mysqli_query($conn, $sql_employee);
    $employee = mysqli_fetch_assoc($result_employee);

    $sql_schedule = "SELECT h.godzinaroz, h.godzinazak, h.data, pr.nazwa AS projekt
                    FROM harmonogram h
                    JOIN projekty pr ON h.idprojekt = pr.idprojekt
                    WHERE h.idpracownika = '$employee_id'
                    ORDER BY h.data ASC";
    $result_schedule = mysqli_query($conn, $sql_schedule);

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);

    $pdf->Cell(0, 10, $employee['imie'] . ' ' . $employee['nazwisko'], 0, 1, 'C');
    $pdf->Ln(10);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(40, 10, 'Data', 1);
    $pdf->Cell(50, 10, 'Godzina rozpoczecia', 1);
    $pdf->Cell(50, 10, 'Godzina zakonczenia', 1);
    $pdf->Cell(50, 10, 'Projekt', 1);
    $pdf->Ln();

    $pdf->SetFont('Arial', '', 12);
    if (mysqli_num_rows($result_schedule) > 0) {
        while($row = mysqli_fetch_assoc($result_schedule)) {
            $pdf->Cell(40, 10, $row["data"], 1);
            $pdf->Cell(50, 10, $row["godzinaroz"], 1);
            $pdf->Cell(50, 10, $row["godzinazak"], 1);
            $pdf->Cell(50, 10, $row["projekt"], 1);
            $pdf->Ln();
        }
    } else {
        $pdf->Cell(190, 10, 'Brak danych w harmonogramie dla tego pracownika.', 1, 1, 'C');
    }

    $pdf->Output('D', 'harmonogram_' . $employee['imie'] . '_' . $employee['nazwisko'] . '.pdf');
}

mysqli_close($conn);
?>