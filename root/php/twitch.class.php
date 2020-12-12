<?php
class Twitch {
  public static function live($channel) {
    echo "<iframe src=\"https://player.twitch.tv/?channel=$channel&amp;parent=" . $_SERVER['HTTP_HOST'] . "\" frameborder=\"0\" scrolling=\"no\" seamless=\"seamless\" allowfullscreen=\"true\"></iframe>";
  }
  public static function chat($channel) {
    echo "<iframe src=\"https://www.twitch.tv/embed/$channel/chat?parent=" . $_SERVER['HTTP_HOST'] . "\" frameborder=\"0\" scrolling=\"no\" seamless=\"seamless\"></iframe>";
  }
}