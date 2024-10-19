<?php
function getCounts() {
    $jsonFile = 'counts.json';
    if (!file_exists($jsonFile)) {
        $counts = [
            'ramune' => 0,
            'ichigo' => 0,
            'lemon' => 0,
            'cola' => 0,
            'normal' => 0,
            'people' => 0
        ];
        file_put_contents($jsonFile, json_encode($counts));
    } else {
        $counts = json_decode(file_get_contents($jsonFile), true);
    }
    return $counts;
}

function updateCount($item, $change) {
    $jsonFile = 'counts.json';
    $data = getCounts();
    if (isset($data[$item])) {
        $data[$item] += $change;
        $data[$item] = max(0, $data[$item]); // 0未満にならないようにする
        file_put_contents($jsonFile, json_encode($data));
        return $data[$item];
    }
    return null;
}

function resetCounts() {
    $jsonFile = 'counts.json';
    $counts = [
        'ramune' => 0,
        'ichigo' => 0,
        'lemon' => 0,
        'cola' => 0,
        'normal' => 0,
        'people' => 0
    ];
    file_put_contents($jsonFile, json_encode($counts));
    return $counts;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    if (isset($_POST['item'])) {
        $item = $_POST['item'];
        $change = (int)$_POST['change'];
        $newCount = updateCount($item, $change);
        if ($newCount !== null) {
            $labels = [
                'ramune' => 'ラムネ',
                'ichigo' => 'いちご',
                'lemon' => 'レモン',
                'cola' => 'コーラ',
                'normal' => 'ノーマル',
                'people' => '来た人数'
            ];
            $message = isset($labels[$item]) ? $labels[$item] : '商品';
            echo json_encode(['count' => $newCount, 'message' => "{$message}の個数が" . ($change > 0 ? '増加' : '減少') . "しました"]);
        } else {
            echo json_encode(['error' => '無効な商品です']);
        }
    } elseif (isset($_POST['reset'])) {
        $counts = resetCounts();
        echo json_encode(['message' => '全ての商品の個数がリセットされました']);
    }
    exit();
}

$counts = getCounts();
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
<body class="bg-gray-100 flex justify-center items-center min-h-screen">

<!-- 管理画面 -->
<div class="bg-white shadow-md rounded p-6 w-full max-w-2xl mx-4" x-data="productManager()">
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

    <div class="text-center mb-4">
        <span class="text-lg">合計個数: <span x-text="totalCount"></span></span>
        <span class="text-lg ml-4">合計金額: <span x-text="totalAmount"></span>円</span>
    </div>

    <!-- <div class="text-center">
        <button @click="resetCounts" class="bg-red-500 text-white px-4 py-2 rounded">リセット</button>
    </div> -->
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
            get totalCount() {
                return this.items.reduce((sum, item) => sum + item.count, 0);
            },
            get totalAmount() {
                return this.totalCount * 100; // 全ての商品が一戸当たり100円
            },
            async updateCount(item, change) {
                try {
                    const response = await fetch("/", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `item=${item}&change=${change}`
                    });
                    const data = await response.json();
                    if (data.count !== undefined) {
                        this.items.find(i => i.name === item).count = data.count;
                        this.message = data.message; // フラッシュメッセージを表示
                    } else if (data.error) {
                        this.message = data.error;
                    }
                } catch (error) {
                    this.message = 'エラーが発生しました';
                }
            },
            async resetCounts() {
                try {
                    const response = await fetch("/", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `reset=true`
                    });
                    const data = await response.json();
                    this.items.forEach(item => item.count = 0);
                    this.message = data.message; // フラッシュメッセージを表示
                } catch (error) {
                    this.message = 'エラーが発生しました';
                }
            }
        };
    }
</script>

</body>
</html>