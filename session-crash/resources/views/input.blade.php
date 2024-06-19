<!DOCTYPE html>
<html lang="ja">
    <head>
        <title>セッションクラッシュ</title>
        <script src="js/input.js"></script>
    </head>
    <body>
        <form id="" method="post">
            @csrf
            名前：&ensp;<input id="name" name="name" type="text"><br>
            コメント：&ensp;<input id="comment" name="comment">
            <p>
                <input type="file" id="imgfile"><br>
                <span id="imgarea"></span>
                <input type="hidden" id="imgdata" name="imgdata" value="">
            </p>
            <input type="submit" value="ぽすと">
        </form>
        <div>
            <pre>{{ print_r(session()->all(), true) }}</pre>
        </div>
    </body>
</html>