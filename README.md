# Requirements

// May be PHP Office requires it

PHP needs to have the zip extension installed.

On Debian and Ubuntu, you can usually install it with:

```shell

sudo apt update
sudo apt install php-zip
```

Then restart your webserver. Example:

```shell
sudo systemctl restart apache2
```

# Работа с вёрсткой 
```shell
npx @tailwindcss/cli -i ./css/about-page-resource.css -o ./css/about-page.css --watch
```

# Назначение директорий

`img/alphabet` картинки букв алфавита нужные при составлении имёни
`img/months` картинки с названиями месяцев 
`img/calendar` картинки для составления календаря
`img/calendar/alphabet` буквы для добавления по одной картинке
`img/calendar/images` наборы картинок для добавления картинок за месяц
`img/calendar/categories` картинки для добавления по категориям
`img/about` картинки для Главной страницы 
`img/shop` картинки для раздела Магазин
