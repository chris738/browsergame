<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Siedlungsaufbau</title>
    <link rel="stylesheet" href="style.css">
    <script src="backend.js" defer></script>
</head>
<body>
    <header class="header">
        <h1>Siedlungsname</h1>
    </header>

    <section class="resources">
        <div class="resource">
            <p>Holz: <span id="holz">0</span></p>
        </div>
        <div class="resource">
            <p>Stein: <span id="stein">0</span></p>
        </div>
        <div class="resource">
            <p>Erz: <span id="erz">0</span></p>
        </div>
        <div class="resource">
        <p>Lager: <span id="lagerKapazität">0</span></p>
    </div>
    </section>

    <section class="buildings">
        <table>
            <thead>
                <tr>
                    <th>Gebäude</th>
                    <th>Stufe</th>
                    <th>Kosten</th>
                    <th>Aktion</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Holzfäller</td>
                    <td><span id="holzfäller">0</span></td>
                    <td>
                        <span class="cost-box" id="holzfällerKostenHolz">0 Holz</span>
                        <span class="cost-box" id="holzfällerKostenStein">0 Stein</span>
                        <span class="cost-box" id="holzfällerKostenErz">0 Erz</span>
                    </td>
                    <td style="text-align: right;"><button onclick="upgradeBuilding('holzfäller')">Upgrade</button></td>
                </tr>
                <tr>
                    <td>Steinbruch</td>
                    <td><span id="steinbruch">0</span></td>
                    <td>
                        <span class="cost-box" id="steinbruchKostenHolz">0 Holz</span>
                        <span class="cost-box" id="steinbruchKostenStein">0 Stein</span>
                        <span class="cost-box" id="steinbruchKostenErz">0 Erz</span>
                    </td>
                    <td style="text-align: right;"><button onclick="upgradeBuilding('steinbruch')">Upgrade</button></td>
                </tr>
                <tr>
                    <td>Erzbergwerk</td>
                    <td><span id="erzbergwerk">0</span></td>
                    <td>
                        <span class="cost-box" id="erzbergwerkKostenHolz">0 Holz</span>
                        <span class="cost-box" id="erzbergwerkKostenStein">0 Stein</span>
                        <span class="cost-box" id="erzbergwerkKostenErz">0 Erz</span>
                    </td>
                    <td style="text-align: right;"><button onclick="upgradeBuilding('erzbergwerk')">Upgrade</button></td>
                </tr>
                <td>Lager</td>
                    <td><span id="lager">0</span></td>
                    <td>
                        <span class="cost-box" id="lagerKostenHolz">0 Holz</span>
                        <span class="cost-box" id="lagerKostenStein">0 Stein</span>
                        <span class="cost-box" id="lagerKostenErz">0 Erz</span>
                    </td>
                    <td style="text-align: right;"><button onclick="upgradeBuilding('lager')">Upgrade</button></td>
        </tr>
            </tbody>
        </table>
    </section>
</body>
</html>
