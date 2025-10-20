<?php
// ==============================
// ツライカウンター（PHP版）
// ==============================
// ●このファイルをサーバーに1つ置くだけで動作します。
// ●アクセスするだけで「つらい」ボタンが押せて、合計・曜日別が共有されます。
// ●サーバー上に `data.json` が自動で作成され、そこにデータを保存します。

$path = __DIR__ . '/data.json';
if(!file_exists($path)){
  file_put_contents($path, json_encode([
    'total' => 0,
    'weekdays' => array_fill(0,7,0)
  ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function load_data($path){
  $json = file_get_contents($path);
  $data = json_decode($json, true);
  if(!$data) $data = ['total'=>0,'weekdays'=>array_fill(0,7,0)];
  return $data;
}

function save_data($path, $data){
  file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// APIリクエスト処理
if(isset($_GET['action'])){
  header('Content-Type: application/json; charset=utf-8');
  $data = load_data($path);
  if($_GET['action']==='get'){
    echo json_encode($data);
    exit;
  }
  if($_GET['action']==='inc'){
    $data['total']++;
    $w = (int)date('w'); // 0(日)〜6(土)
    $data['weekdays'][$w]++;
    save_data($path, $data);
    echo json_encode($data);
    exit;
  }
}

?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ツライカウンター（共有版）</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    @keyframes throb{0%{transform:scale(1)}50%{transform:scale(1.05)}100%{transform:scale(1)}}
    .throb{animation:throb .25s ease-in-out}
  </style>
</head>
<body class="min-h-screen bg-gradient-to-b from-slate-900 via-slate-900 to-slate-800 text-slate-100">
  <main class="mx-auto max-w-3xl px-6 py-16 flex flex-col items-center gap-10">
    <header class="text-center">
      <h1 class="text-4xl md:text-5xl font-black tracking-tight">ツライカウンター</h1>
      <p class="mt-3 text-slate-300">『つらい』を押すと世界共通カウントが増えます。</p>
    </header>

    <section class="w-full">
      <div class="rounded-2xl shadow-2xl border border-white/10 p-8 text-center bg-white/5">
        <div class="text-sm uppercase tracking-widest text-slate-400">TOTAL</div>
        <div id="count" class="mt-1 text-6xl md:text-7xl font-extrabold tabular-nums">0</div>
        <div id="delta" class="mt-2 text-sm text-slate-400 h-5"></div>
        <button id="btn" class="mt-6 inline-flex items-center justify-center w-full md:w-auto gap-3 rounded-2xl px-8 py-5 text-2xl font-bold shadow-xl border border-white/10 bg-pink-600 hover:bg-pink-500 active:translate-y-[1px] focus:outline-none focus:ring-4 focus:ring-pink-400/50">
          <span>つらい</span>
        </button>
      </div>
    </section>

    <section class="w-full">
      <div class="rounded-2xl border border-white/10 p-6 bg-white/5">
        <div class="flex items-baseline justify-between">
          <h2 class="text-xl font-bold">曜日別つらさ合計</h2>
        </div>
        <div class="mt-4">
          <canvas id="weekdayChart" height="140"></canvas>
        </div>
      </div>
    </section>

    <footer class="text-center text-xs text-slate-400">
      <p>Made with ❤️ — PHP 1ファイル版</p>
    </footer>
  </main>

  <script>
    const $ = (s)=>document.querySelector(s);
    const countEl = $('#count');
    const deltaEl = $('#delta');
    const btn = $('#btn');
    let chart;

    async function fetchData(action='get'){
      const res = await fetch(`?action=${action}&_=${Date.now()}`);
      return await res.json();
    }

    function render(data){
      countEl.textContent = data.total.toLocaleString('ja-JP');
      const labels=['日','月','火','水','木','金','土'];
      const ctx=document.getElementById('weekdayChart').getContext('2d');
      if(chart) chart.destroy();
      chart=new Chart(ctx,{
        type:'bar',
        data:{labels,datasets:[{label:'曜日別合計',data:data.weekdays,borderWidth:1}]},
        options:{scales:{y:{beginAtZero:true,ticks:{precision:0}}},plugins:{legend:{display:false}}}
      });
    }

    function throb(el){el.classList.remove('throb');void el.offsetWidth;el.classList.add('throb');}

    async function init(){
      const data=await fetchData('get');
      render(data);
      btn.addEventListener('click',async()=>{
        throb(btn);throb(countEl);
        deltaEl.textContent='+1';
        setTimeout(()=>deltaEl.textContent='',1500);
        const updated=await fetchData('inc');
        render(updated);
      });
    }

    init();
  </script>
</body>
</html>
