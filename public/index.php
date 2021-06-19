<?php

use IsaEken\NetworkMonitor\NetworkMonitor;

require_once __DIR__ . '/../vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (! NetworkMonitor::hasInterface($_POST['interface'] ?? '')) {
        return print json_encode((object) [
            'name' => null,
            'directory' => null,
            'statistics' => (object) [],
        ]);
    }

    $interface = NetworkMonitor::findInterface($_POST['interface'] ?? '');
    return print json_encode((object) [
        'name' => $interface->getName(),
        'directory' => $interface->directory,
        'statistics' => (object) $interface->statistics()->toArray(),
    ]);
}

$composer = json_decode(file_get_contents(__DIR__ . '/../composer.json'));
$version = $composer->version;
$title = sprintf('Network Monitor - v%s', $version);

?><!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.2/tailwind.min.css" integrity="sha512-ghzNCvgK81aIdKyuTnLazeFEzs2F8AHLWyCYsvJHPqgGf8OpS/yRrq6seFxik5n08mjRmX8ETGPrHKkDkwBekw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/2.3.0/alpine.js" integrity="sha512-nIwdJlD5/vHj23CbO2iHCXtsqzdTTx3e3uAmpTm4x2Y8xCIFyWu4cSIV8GaGe2UNVq86/1h9EgUZy7tn243qdA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.21.1/axios.min.js" integrity="sha512-bZS47S7sPOxkjU/4Bt0zrhEtWx0y0CRkhEp8IckzK+ltifIIE9EMIMTuT/mEzoIMewUINruDBIR/jJnbguonqQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</head>
<body class="bg-gray-900 text-gray-100 min-w-screen">
<div class="max-w-4xl my-12 mx-auto space-y-4" x-data="data()">
    <div>
        <div class="text-2xl">
            <?= $title ?>
        </div>
        <div class="text-xl">
            <span class="text-xs mr-2">developed by</span><a href="https://isaeken.com.tr" target="_blank">Isa Eken</a>
        </div>
    </div>
    <div>
        <label for="interfaces">Select Interface</label>
        <select x-on:change="interface = $event.target.value; updateInterval(); load();" name="interfaces" id="interfaces" class="block w-full py-2 text-gray-700">
            <?php foreach (NetworkMonitor::getInterfaces() as $interface): ?>
                <option x-bind:value="'<?= $interface->getName() ?>'" value="<?= $interface->getName() ?>" <?= $interface->getName() === 'eth0' ? 'selected' : '' ?>>
                    <?= $interface->getName() ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="refresh">Refresh</label>
        <select x-on:change="interval = $event.target.value; updateInterval(); load();" name="refresh" id="refresh" class="block w-full py-2 text-gray-700">
            <?php $index = 0; foreach ([0, 5, 15, 30, 60, 300, 1800, 3600, 43200] as $seconds): ?>
                <option x-bind:value="<?= $seconds ?>" value="<?= $seconds ?>" <?= $index++ === 0 ? 'selected' : '' ?>>
                    <?php
                    if ($seconds === 0) {
                        echo 'No refresh';
                    }
                    else {
                        $datetime1 = new DateTime('@0');
                        $datetime2 = new DateTime("@$seconds");
                        echo $datetime1->diff($datetime2)->format('%a days, %h hours, %i minutes and %s seconds');
                    }
                    ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="text-xl" x-show="time_left !== null && time_left > 0">
        Refreshing in <span x-text="time_left" id="time_left">0</span> seconds
    </div>
    <div>
        <div id="interface_name" x-text="interface" class="mb-4 text-xl"></div>
        <table class="w-full">
            <template x-for="item in statistics">
                <tr class="hover:bg-gray-800">
                    <td class="py-2" x-text="item[0]"></td>
                    <td class="py-2" x-text="item[1]"></td>
                </tr>
            </template>
        </table>
    </div>
    <div class="mb-4">
        <p>
            Please run
            <code class="bg-black rounded text-sm mx-2 px-2 py-1 select-all">sudo apt-get install vnstat</code>
            and
            <code class="bg-black rounded text-sm mx-2 px-2 py-1 select-all">sudo service vnstat enable && sudo service vnstat start</code>
            for the app to work properly.
        </p>
    </div>
    <script>
        let int = 'eth0';
        let statistics = [];

        function load() {
            loadData(document.querySelector('[x-data]').__x.$data.interface).then(function (data) {
                document.querySelector('[x-data]').__x.$data.statistics = data;
            });
        }

        function data() {
            return {
                interface: int,
                statistics: statistics,
                time_left: 0,
                time_left__: null,
                interval: 0,
                interval__: null,
                updateInterval: function () {
                    this.time_left = this.interval;
                    let time_left = this.time_left;
                    let interval = this.interval;

                    clearInterval(this.interval__);
                    clearInterval(this.time_left__);

                    if (interval > 1) {
                        this.interval__ = setInterval(function () {
                            load();
                            time_left = interval;
                        }, interval * 1000);

                        this.time_left__ = setInterval(function () {
                            time_left = time_left - 1;
                            document.getElementById('time_left').textContent = time_left;
                        }, 1000);
                    }

                    this.time_left = time_left;
                },
                load: function () {
                    load();
                }
            };
        }

        async function loadData(interfaceName) {
            let statistics = [];
            const params = new URLSearchParams();
            params.append('interface', interfaceName);

            await axios({
                method: 'POST',
                url: '<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>',
                data: params,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
            }).then(function (response) {
                statistics = Object.entries(response.data.statistics);
            });

            return statistics;
        }
    </script>
</div>
</body>
</html>
