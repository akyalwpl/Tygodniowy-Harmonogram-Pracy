<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tygodniowy Harmonogram Pracy</title>
    <link rel="stylesheet" href="styl.css">
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
        <nav>
            <a href="dodaj.php" class="nav-link">Aktualizuj harmonogram</a>
            <a href="pdfharmonogram.php" class="nav-link">Generuj PDF Harmonogramu</a>
        </nav>
    </header>

    <div class="auto-refresh-container">
        <label for="auto_refresh">Automatyczne odświeżanie  </label>
        <input type="checkbox" id="auto_refresh" <?php echo (isset($_GET['auto_refresh']) && $_GET['auto_refresh'] == 'false') ? '' : 'checked'; ?>>
        <label for="show_descriptions" style="margin-left: 2rem;">Wyświetlanie opisów  </label>
        <input type="checkbox" id="show_descriptions" <?php echo (isset($_GET['show_descriptions']) && $_GET['show_descriptions'] == 'false') ? '' : 'checked'; ?>>
        <label for="column_width" style="margin-left: 2rem;">Szerokość Kolumny</label>
        <input type="number" id="column_width" placeholder="px" min="50" max="500">
        <button id="reset_width">Reset</button>
    </div>

    <div class="filter-container">
        <form method="GET" action="">
            <input type="hidden" name="auto_refresh" id="auto_refresh_hidden" value="<?php echo isset($_GET['auto_refresh']) ? $_GET['auto_refresh'] : 'true'; ?>">
            <input type="hidden" name="show_descriptions" id="show_descriptions_hidden" value="<?php echo isset($_GET['show_descriptions']) ? $_GET['show_descriptions'] : 'true'; ?>">
            <label for="filter_date">Filtruj po dacie:</label>
            <input type="date" id="filter_date" name="filter_date" placeholder="01.01.1999" value="<?php echo isset($_GET['filter_date']) ? $_GET['filter_date'] : '2025-01-01'; ?>">
            <button type="submit" class="filter-btn">Filtruj</button>
            <a href="index.php?<?php echo isset($_GET['auto_refresh']) ? 'auto_refresh=' . $_GET['auto_refresh'] : 'auto_refresh=true'; ?>&<?php echo isset($_GET['show_descriptions']) ? 'show_descriptions=' . $_GET['show_descriptions'] : 'show_descriptions=true'; ?>" class="reset-btn">Resetuj</a>
        </form>
    </div>

    <div class="table-container">
        <table>
            <tr>
                <th><input type="checkbox" class="column-checkbox" data-column="0"> Imię</th>
                <th><input type="checkbox" class="column-checkbox" data-column="1"> Nazwisko</th>
                <th><input type="checkbox" class="column-checkbox" data-column="2"> Godzina Rozpoczęcia</th>
                <th><input type="checkbox" class="column-checkbox" data-column="3"> Godzina Zakończenia</th>
                <th><input type="checkbox" class="column-checkbox" data-column="4"> Data</th>
                <th><input type="checkbox" class="column-checkbox" data-column="5"> Projekt</th>
            </tr>
            <style>
                .project-cell {
                    font-size: 0.9em;
                    max-width: 350px;
                }
                .project-name {
                    font-weight: bold;
                    font-size: 1em;
                }
                .project-description {
                    font-size: 0.8em;
                    color: #666;
                    font-style: italic;
                    display: block;
                }
                .project-description.hidden {
                    display: none;
                }
            </style>
            <?php
                $sql = "SELECT p.imie, p.nazwisko, h.godzinaroz, h.godzinazak, h.data, pr.nazwa AS projekt, pr.opis AS opis_projektu
                        FROM harmonogram h
                        JOIN pracownicy p ON h.idpracownika = p.idpracownika
                        JOIN projekty pr ON h.idprojekt = pr.idprojekt";

                if (isset($_GET['filter_date']) && !empty($_GET['filter_date'])) {
                    $filter_date = mysqli_real_escape_string($conn, $_GET['filter_date']);
                    $sql .= " WHERE h.data = '$filter_date'";
                }

                $sql .= " ORDER BY h.data DESC";
                $result = mysqli_query($conn, $sql);

                if (mysqli_num_rows($result) > 0) {
                    while($row = mysqli_fetch_assoc($result)) {
                        echo "<tr><td>" . $row["imie"] . "</td><td>" . $row["nazwisko"] . "</td><td>" . $row["godzinaroz"] . "</td><td>" . $row["godzinazak"] . "</td><td>" . $row["data"] . "</td><td class='project-cell'><div class='project-name'>" . $row["projekt"] . "</div><div class='project-description' id='desc-" . $row["projekt"] . "'>" . $row["opis_projektu"] . "</div></td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>Brak danych w harmonogramie</td></tr>";
                }
                mysqli_close($conn);
            ?>
        </table>
    </div>

    <script>
        var interval;
        function startRefresh() {
            interval = setInterval(function() {
                location.reload();
            }, 10000);
        }
        function stopRefresh() {
            clearInterval(interval);
        }
        function updateHiddenFields() {
            document.getElementById('auto_refresh_hidden').value = document.getElementById('auto_refresh').checked ? 'true' : 'false';
            document.getElementById('show_descriptions_hidden').value = document.getElementById('show_descriptions').checked ? 'true' : 'false';
        }

        document.getElementById('auto_refresh').addEventListener('change', function() {
            updateHiddenFields();
            if (this.checked) {
                startRefresh();
            } else {
                stopRefresh();
            }
            
            var url = new URL(window.location);
            url.searchParams.set('auto_refresh', this.checked ? 'true' : 'false');
            window.history.replaceState({}, '', url);
        });

        document.getElementById('show_descriptions').addEventListener('change', function() {
            updateHiddenFields();
            var descriptions = document.querySelectorAll('.project-description');
            descriptions.forEach(function(desc) {
                if (this.checked) {
                    desc.classList.remove('hidden');
                } else {
                    desc.classList.add('hidden');
                }
            }.bind(this));

            // Update URL parameter
            var url = new URL(window.location);
            url.searchParams.set('show_descriptions', this.checked ? 'true' : 'false');
            window.history.replaceState({}, '', url);
        });

        // Initialize hidden fields and apply current settings
        updateHiddenFields();
        if (document.getElementById('show_descriptions').checked) {
            var descriptions = document.querySelectorAll('.project-description');
            descriptions.forEach(function(desc) {
                desc.classList.remove('hidden');
            });
        } else {
            var descriptions = document.querySelectorAll('.project-description');
            descriptions.forEach(function(desc) {
                desc.classList.add('hidden');
            });
        }

        if (document.getElementById('auto_refresh').checked) {
            startRefresh();
        }

        // Update hidden fields when form is submitted
        document.querySelector('.filter-container form').addEventListener('submit', function() {
            updateHiddenFields();
        });

        // Column selection and width functionality
        const columnCheckboxes = document.querySelectorAll('.column-checkbox');
        const columnWidthInput = document.getElementById('column_width');
        const resetWidthBtn = document.getElementById('reset_width');
        const table = document.querySelector('table');

        // Load saved column widths from localStorage
        function loadColumnWidths() {
            const savedWidths = JSON.parse(localStorage.getItem('columnWidths') || '{}');
            Object.keys(savedWidths).forEach(colIndex => {
                const width = savedWidths[colIndex];
                const th = table.querySelector(`th:nth-child(${parseInt(colIndex) + 1})`);
                const tds = table.querySelectorAll(`td:nth-child(${parseInt(colIndex) + 1})`);
                if (th) th.style.width = width + 'px';
                tds.forEach(td => td.style.width = width + 'px');
            });
        }

        // Load saved checkbox states from localStorage
        function loadCheckboxStates() {
            const savedStates = JSON.parse(localStorage.getItem('checkboxStates') || '{}');
            columnCheckboxes.forEach((checkbox, index) => {
                if (savedStates[index]) {
                    checkbox.checked = true;
                }
            });
        }

        // Save checkbox states to localStorage
        function saveCheckboxStates() {
            const states = {};
            columnCheckboxes.forEach((checkbox, index) => {
                states[index] = checkbox.checked;
            });
            localStorage.setItem('checkboxStates', JSON.stringify(states));
        }

        // Save column widths to localStorage
        function saveColumnWidths() {
            const widths = {};
            columnCheckboxes.forEach((checkbox, index) => {
                if (checkbox.checked) {
                    const width = parseInt(columnWidthInput.value) || 100;
                    widths[index] = width;
                }
            });
            localStorage.setItem('columnWidths', JSON.stringify(widths));
        }

        // Apply width to selected columns
        function applyWidthToSelectedColumns() {
            const width = parseInt(columnWidthInput.value) || 100;
            columnCheckboxes.forEach((checkbox, index) => {
                if (checkbox.checked) {
                    const th = table.querySelector(`th:nth-child(${index + 1})`);
                    const tds = table.querySelectorAll(`td:nth-child(${index + 1})`);
                    if (th) th.style.width = width + 'px';
                    tds.forEach(td => td.style.width = width + 'px');
                }
            });
            saveColumnWidths();
        }

        // Reset column widths
        function resetColumnWidths() {
            columnCheckboxes.forEach((checkbox, index) => {
                const th = table.querySelector(`th:nth-child(${index + 1})`);
                const tds = table.querySelectorAll(`td:nth-child(${index + 1})`);
                if (th) th.style.width = '';
                tds.forEach(td => td.style.width = '');
            });
            localStorage.removeItem('columnWidths');
            columnWidthInput.value = '';
        }

        // Event listeners
        columnCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', saveCheckboxStates);
        });
        columnWidthInput.addEventListener('input', applyWidthToSelectedColumns);
        resetWidthBtn.addEventListener('click', resetColumnWidths);

        // Load saved states and widths on page load
        loadCheckboxStates();
        loadColumnWidths();
    </script>


</body>
</html>