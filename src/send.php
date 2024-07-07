<?php

use Idoalit\S2i\Libs\IndexHelper;
use Idoalit\SlimsEloquentModels\Biblio;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $input = json_decode(file_get_contents('php://input'), true);

    switch ($input['action']) {
        case 'count':
            echo Biblio::count();
            break;

        case 'send':
            try {
                $biblio = Biblio::take(1)->skip((int)$input['index'])->first();
                $biblio = IndexHelper::run($biblio);
                echo 'The _' . substr($biblio->title, 0, 50) . '_ has been sent with '. $biblio->itemStatus['sentCount'] .' items 🛬';
            } catch (\Throwable $th) {
                echo $th->getMessage();
            }
            break;

        default:
            # code...
            break;
    }
    exit;
}
?>
<div class="menuBox">
    <div class="menuBoxInner printIcon">
        <div class="per_title">
            <h2><?php echo __('Send 📦 to Inlislite DB'); ?></h2>
        </div>
        <div class="infoBox">Nyatanya <b>mahal</b> belum tentu <b>nyaman</b> pun dengan <b>lite</b> belum tentu <b>ringan</b> 😔</div>
    </div>
</div>

<div class="container-fluid my-3">
    <div class="row d-flex align-items-center">
        <div class="col-1 col-sm-2">
            <button class="btn btn-primary w-100" onclick="startSend()">Start 🚀</button>
        </div>
        <div class="col-11 col-sm-10">
            <div class="progress">
                <div class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
    </div>
</div>
<div id="log" class="w-100 bg-dark text-white" style="height: 300px; overflow-y: scroll; padding: 10px;"></div>

<script>
    const baseUrl = `<?= $_SERVER['PHP_SELF'] . '?mod=' . $_GET['mod'] . '&id=' . $_GET['id'] ?>`
    const progres = document.querySelector('.progress-bar')
    let biblioCount = 0;

    function addLog(message) {
        const logDiv = document.getElementById('log');
        const newLog = document.createElement('div');
        newLog.textContent = `${new Date().toLocaleString()} :: ${message}`;
        logDiv.appendChild(newLog);
        logDiv.scrollTop = logDiv.scrollHeight;
    }

    async function startSend() {
        for (let index = 0; index < biblioCount; index++) {
            addLog(`Send index ${index} ✈️`)
            const res = await fetch(baseUrl, {
                method: 'post',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'send',
                    index
                })
            })
            const message = await res.text();
            addLog(message)
            progres.style.width = `${((index+1) / biblioCount) * 100}%`
        }
    }

    fetch(baseUrl, {
        method: 'post',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'count'
        })
    }).then(res => res.text()).then(res => {
        biblioCount = Number(res)
        addLog(`Your bibliography: ${res} title(s)`)
    });
</script>
