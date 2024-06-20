# Laravelのセッション格納にmemcachedを使用した場合にセッションがクラッシュする例

## 1. 目的など

昨今では、Redisを使用したり、セッションにmemcachedを使用することはそもそもないと思われるが、  ある機能開発中に、セッションが壊れるという事態が発生したため、その原因、対策を述べておく

## 2. ライセンスなど

MITライセンスです  
サンプルソースなどで起こったいかなる不具合も責任を負いません  
自己責任でお願いいたします

## 3. 環境構築

### 3-1. はじめに

実際の環境は、Laravel5系とCentOS7という古いものであるが(2024-05現在も稼働中)、Laravel11でも起こるため、以下の環境で設定する  
Laravelとmemcachedの環境がすでにあるならここは飛ばしてもかまいません

### 3-2. 環境

<table>
    <tr>
        <td><strong>OS</strong></td>
        <td>AlmaLinux 9(9.4：執筆時点)</td>
    </tr>
    <tr>
        <td><strong>ウェブサーバアプリケーション</strong></td>
        <td>apache(artisan serveでも多分問題なし)</td>
    </tr>
    <tr>
        <td><strong>PHP</strong></td>
        <td>8(8.3：執筆時点)</td>
    </tr>
    <tr>
        <td><strong>Laravel</strong></td>
        <td>11(執筆時点)</td>
    </tr>
    <tr>
        <td><strong>memcached</strong></td>
        <td>1.6(執筆時点)</td>
    </tr>
</table>

### 3-3. apacheのインストール

```console
# dnf install apache
```

### 3-4. RemiからPHPのインストール

```console
# dnf install https://rpms.remirepo.net/enterprise/remi-release-9.rpm
# dnf install php83-php php83-php-mbstring php83-php-memcached php83-php-zip unzip
# alternatives --install /usr/bin/php php /usr/bin/php83 1
```

### 3-5. Composerのインストール

```console
# php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
# php composer-setup.php
# mv composer.phar /usr/local/bin/composer
```

### 3-6. Laravelのインストール

```console
$ composer create-project --prefer-dist laravel/laravel {app名}
```

今回はapp名はsession-crashとした

### 3-7. memcachedのインストール

```console
# dnf install memcached
```

### 3-8. SELINUXの無効化

本番環境等では適切に設定してください  
本検証ではオフにしておきます

```console
# cp -p /etc/selinux/config /etc/selinux/config.default
# vi /etc/selinux/config
SELINUX=enforcing
↓
SELINUX=disabled
# reboot
```

disabledの変更にして、再起動

### 3-9. firewalldの無効化

本番環境等では適切に設定してください  
本検証ではオフにしておきます

```console
# systemctl disable firewalld[.service]
# systemctl stop firewalld[.service]
```

## 4. 各種設定

### 4-1. apacheの設定

conf設定の例

```console
DocumentRoot {laravelインストールディレクトリ}/public
<Directory {laravelインストールディレクトリ}/public>
    Options +FollowSymLinks -Indexes
    AllowOverride All
    Require all granted
</Directory>
```

変更したら起動

```console
# systemctl enable httpd[.service]
# systemctl start httpd[.service]
```

### 4-2. memcachedの設定

systemdの設定ファイル  
/lib/systemd/system/memcached.service
により、  
/etc/sysconfig/memcached  
に設定が記述されているが、一旦はデフォルトのまま起動

```console
# less /lib/systemd/system/memcached.service
〜 省略 〜
[Service]
EnvironmentFile=/etc/sysconfig/memcached
ExecStart=/usr/bin/memcached -p ${PORT} -u ${USER} -m ${CACHESIZE} -c ${MAXCONN} $OPTIONS
〜 省略 〜
```
となっているので、  
/etc/sysconfig/memcached  
の設定値で起動する

```console
# cat /etc/sysconfig/memcached
PORT="11211"
USER="memcached"
MAXCONN="1024"
CACHESIZE="64"
OPTIONS="-l 127.0.0.1,::1"
```

確認したら起動

```console
# systemctl enable memcached[.service]
# systemctl start memcached[.service]
```

### 4-3. laravelの設定

.envに以下を設定  
今回はmemcachedでのセッションクラッシュ検証だけなので以下だけでOK.

```console
# vi {laravelインストールディレクトリ}/.env
SESSION_DRIVER=memcached
CACHE_STORE=memcached
```

## 5. memcachedの動作確認

一旦、Laravelからmemcachedに書き込まれるか確認する

### 5-1. memcachedを再起動

memcachedを再起動して、キャッシュが残っていないことを確認

```console
# systemctl restart memcached[.service]
# memcached-tool localhost

#  Item_Size  Max_age   Pages   Count   Full?  Evicted Evict_Time OOM
```
なにも登録されていない

### 5-2. laravelからSession書き込み

{laravelインストールディレクトリ}/routes/web.php  
に以下を追加
```php
Route::get('/', function () {
    session(['foo' => 'bar']); // この行を追加
    return view('welcome');
});
```

ブラウザよりアクセス  
(自分の場合、http://{該当のIPアドレス})

### 5-3. memcachedに保存されたか確認

```console
$ memcached-tool localhost
  #  Item_Size  Max_age   Pages   Count   Full?  Evicted Evict_Time OOM
  6     304B         9s       1       1      no        0        0    0
```

memcachedにセッションが書き込まれているようならOK.


## 6. サンプル画像について

以下のサイトより  
[Sample Videos](https://sample-videos.com)  
[Download Sample JPG Image](https://sample-videos.com/download-sample-jpg-image.php)

sample-image  
中に約500kbと1MBのテスト画像

## 7. サンプルソースの動作確認

サンプルソースにて、  
http://xxx.xxx.xxx.xxx/input  
にアクセスして、sample-imageの500kbまたは1MBのイメージを選択後、「名前」の部分を入力せずに「ぽすと」ボタンを押下し、バリデーションエラーを起こす

- 500kbの画像 → セッションの_old_inputに値が格納される
- 1MBの画像 → セッションが壊れ値が格納されない

## 8. 原因

memcachedのデフォルトの最大キャッシュサイズは1MBであるため  
選択した画像をbase64形式にして、input  type="hidden"に突っ込んでPOSTさせているため、1MBの方でバリデーションエラーが起こるとセッションに格納できない  
→ 実際の案件では、バリデーションエラー時にログインセッションが切れるということが起こった

[memcached(gihyo.jp)](https://gihyo.jp/dev/feature/01/memcached_advanced/0001)  
[最大キャッシュオブジェクトサイズ](https://gihyo.jp/dev/feature/01/memcached_advanced/0001#sec5_ha)

## 9. memcachedのログ出力

デフォルトだと出ない  
ログが吐かれるように、  
/etc/sysconfig/memcached  
にログが吐かれるようにオプションを入れましょう

```console
#OPTIONS="-l 127.0.0.1,::1"
OPTIONS="-l 127.0.0.1,::1 -vv"
```

「-vv」と「-vvv」もあります  
ちなみに「-v」だとjournalctlでログが吐き出されなかった…

## 10. 対応策

- memcachedの最大キャッシュサイズを1MBより上げる → 「-I 2m」とかで可能だが、本当にキャッシュサーバのサイズを上げても大丈夫か考慮が必要
- FormRequestのprepareForValidation()で大きいサイズのものはファイル等に格納してrequestから削除してvalidationの対象から外してvalidation後に戻す

## 11. 感想

- Laravelに対して → いらんことするな(正直、_old_inputをsessionに突っ込むかどうかのオプションとかないの!?)
- memcached(RHELのmemcachedパッケージ)に対して → なんでデフォルトの起動オプションだとログ吐かれないの!?
- そもそもなんで今日日~~クソ~~IEを考慮しないといけないのか…(ECサイトだからそういう方がチャリンチャリンしていただいてるかもしれないのも無視できないんです…w)
