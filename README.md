#### Introduction

---
- **Easy** - Easy to use, suppose format return.
- **Free** - Under MIT license, you can use it anywhere if you want.

#### Requirement

---
PHP 5.6+ and BCMath, Curl, OpenSSL extension installed

#### Start

- step 1:
```
$ git clone https://github.com/JoouA/163Downloader.git
```
- step 2:
```
$ cd 163Downloader
```
> Edit "list.txt" file  write the songs you want to download.


- step 3:
```
$ php index.php
```

#### Cookie

---

You can use your own cookie, you can use chrome to see your cookie;

```
$spider = new Spider();

$cookie = '';

$spider->setCookie($cookie);
```

#### Download

---

```
$spider = new Spider();
$sing_name = 'Heal the World';
$spride->download($song_name);
```






