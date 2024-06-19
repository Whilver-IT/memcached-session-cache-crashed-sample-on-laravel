<!DOCTYPE html>
<html lang="ja">
    <head>
        <title>セッションクラッシュ確認画面</title>
    </head>
    <body>
        <div>
            <pre>{{ print_r(request()->all(), true) }}</pre>
        </div>
    </body>
</html>