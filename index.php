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
    </section>

    <section class="buildings">
        <table>
            <thead>
                <tr>
                    <th>Gebäude</th>
                    <th>Stufe</th>
                    <th>Kosten</th>
                    <th style="text-align: right;">Aktion</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Holzfäller</td>
                    <td><span id="holzfaeller">0</span></td>
                    <td>
                        <span id="holzfaellerKostenHolz">0 Holz</span>, 
                        <span id="holzfaellerKostenStein">0 Stein</span>,
                        <span id="holzfaellerKostenErz">0 Erz</span>
                    </td>
                    <td style="text-align: right;"><button onclick="upgradeBuilding('holzfaeller')">Upgrade</button></td>
                </tr>
                <tr>
                    <td>Steinbruch</td>
                    <td><span id="steinbruch">0</span></td>
                    <td>
                        <span id="steinbruchKostenHolz">0 Holz</span>, 
                        <span id="steinbruchKostenStein">0 Stein</span>,
                        <span id="steinbruchKostenErz">0 Erz</span>
                    </td>
                    <td style="text-align: right;"><button onclick="upgradeBuilding('steinbruch')">Upgrade</button></td>
                </tr>
                <tr>
                    <td>Erzbergwerk</td>
                    <td><span id="erzbergwerk">0</span></td>
                    <td>
                        <span id="erzbergwerkKostenHolz">0 Holz</span>, 
                        <span id="erzbergwerkKostenStein">0 Stein</span>, 
                        <span id="erzbergwerkKostenErz">0 Erz</span>
                    </td>
                    <td style="text-align: right;"><button onclick="upgradeBuilding('erzbergwerk')">Upgrade</button></td>
                </tr>
            </tbody>
        </table>
    </section>
</body>
</html>
