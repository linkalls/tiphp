<?php
// 商品個数の更新処理（非同期リクエスト）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item'])) {
    $jsonFile = 'counts.json';
    $data = json_decode(file_get_contents($jsonFile), true);

    $item = $_POST['item'];
    $change = (int)$_POST['change'];

    if (isset($data[$item])) {
        $data[$item] += $change;
        $data[$item] = max(0, $data[$item]); // 0未満にならないようにする
        file_put_contents($jsonFile, json_encode($data));
        
        // 商品のラベル名を取得
        $labels = [
            'ramune' => 'ラムネ',
            'ichigo' => 'いちご',
            'lemon' => 'レモン',
            'cola' => 'コーラ',
            'normal' => 'ノーマル',
            'people' => '来た人数'
        ];
        
        // メッセージにラベル名を使用
        $message = isset($labels[$item]) ? $labels[$item] : '商品';
        echo json_encode(['count' => $data[$item], 'message' => "{$message}の個数が" . ($change > 0 ? '増加' : '減少') . "しました"]);
    } else {
        echo json_encode(['error' => '無効な商品です']);
    }
    exit();
}

// JSONから現在の個数を取得
$jsonFile = 'counts.json';
$counts = json_decode(file_get_contents($jsonFile), true);
if (!$counts) {
    $counts = [
        'ramune' => 0,
        'ichigo' => 0,
        'lemon' => 0,
        'cola' => 0,
        'normal' => 0,
        'people' => 0
    ];
    file_put_contents($jsonFile, json_encode($counts));
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注文管理システム</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="//unpkg.com/alpinejs" defer></script>
    <script src="https://unpkg.com/htmx.org@1.9.3"></script>
</head>
<body class="bg-gray-100 flex justify-center items-center h-screen">

<!-- 管理画面 -->
<div class="bg-white shadow-md rounded p-6 w-full max-w-lg" x-data="productManager()">
    <!-- フラッシュメッセージを固定表示 -->
    <div x-show="message" x-transition x-init="setTimeout(() => message = '', 3000)" 
         class="bg-green-200 text-green-800 p-2 rounded mb-4 fixed top-0 left-0 right-0 m-4 transition-transform duration-300 transform" 
         x-bind:class="{ 'translate-y-0': message, 'translate-y-[-100%]': !message }">
        <span x-text="message"></span>
    </div>
    
    <h2 class="text-xl mb-2 text-center">来た人数の管理</h2>
    <div class="flex justify-center mb-4">
        <button @click="updateCount('people', 1)" class="bg-blue-500 text-white px-4 py-2 rounded mx-1">+</button>
        <span class="text-lg mx-4">来た人数: <span x-text="items.find(i => i.name === 'people').count"></span></span>
        <button @click="updateCount('people', -1)" class="bg-red-500 text-white px-4 py-2 rounded mx-1">-</button>
    </div>

    <h1 class="text-2xl mb-4 text-center">商品管理</h1>
    <table class="w-full mb-4">
        <tr class="border-b">
            <th class="text-left py-2">商品名</th>
            <th class="text-center py-2">個数</th>
            <th class="text-center py-2">操作</th>
        </tr>
               <template x-for="item in items" :key="item.name">
            <tr class="border-b" x-show="item.label !== '来た人数'">
                <td class="py-2" x-text="item.label"></td>
                <td class="text-center py-2" x-text="item.count"></td>
                <td class="text-center py-2">
                    <template x-if="item.name !== 'people'">
                        <div>
                            <button @click="updateCount(item.name, 1)" class="bg-blue-500 text-white px-4 py-2 rounded mx-1">+</button>
                            <button @click="updateCount(item.name, -1)" class="bg-red-500 text-white px-4 py-2 rounded mx-1">-</button>
                        </div>
                    </template>
                </td>
            </tr>
        </template>
    </table>
</div>

<script>
    function productManager() {
        return {
            items: [
                { name: 'ramune', label: 'ラムネ', count: <?= $counts['ramune'] ?> },
                { name: 'ichigo', label: 'いちご', count: <?= $counts['ichigo'] ?> },
                { name: 'lemon', label: 'レモン', count: <?= $counts['lemon'] ?> },
                { name: 'cola', label: 'コーラ', count: <?= $counts['cola'] ?> },
                { name: 'normal', label: 'ノーマル', count: <?= $counts['normal'] ?> },
                { name: 'people', label: '来た人数', count: <?= $counts['people'] ?> }
            ],
            message: '',
            updateCount(item, change) {
                fetch("/", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `item=${item}&change=${change}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.count !== undefined) {
                        this.items.find(i => i.name === item).count = data.count;
                        this.message = data.message; // Flashメッセージを表示
                    }
                });
            }
        };
    }
</script>

</body>
</html>