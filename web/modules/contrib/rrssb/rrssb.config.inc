<?php

/**
 * @file
 * Buttons Configuration File for RRSSB.
 */

/**
 * Implements hook_rrssb_buttons().
 */
function rrssb_rrssb_buttons() {
  $buttons = [
    'delicious' => [
      'share_url' => 'https://delicious.com/save?v=5&noui&jump=close&url=[rrssb:url]&title=[rrssb:title]',
      'color' => '#0b79e5',
      'color_hover' => '#095fb4',
    ],
    'email' => [
      'share_url' => 'mailto:?subject=[rrssb:title]&body=[rrssb:url]',
      'color' => '#0a88ff',
      'color_hover' => '#006ed6',
      'popup' => FALSE,
    ],
    'facebook' => [
      'share_url' => 'https://www.facebook.com/sharer/sharer.php?u=[rrssb:url]',
      'follow_url' => 'https://www.facebook.com/[rrssb:username]',
      'color' => '#306199',
      'color_hover' => '#244872',
    ],
    'github' => [
      'follow_url' => 'https://github.com/[rrssb:username]',
      'color' => '#444',
      'color_hover' => '#2b2b2b',
    ],
    'hackernews' => [
      'share_url' => 'https://news.ycombinator.com/submitlink?u=[rrssb:url]&t=[rrssb:title]&text=[rrssb:title]',
      'color' => '#f60',
      'color_hover' => '#cc5200',
    ],
    'instagram' => [
      'follow_url' => 'https://instagram.com/[rrssb:username]',
      'color' => '#125688',
      'color_hover' => '#0c3a5b',
    ],
    'join' => [
      'follow_url' => '/user/register',
      'color' => '#dc143c',
      'color_hover' => '#ad102f',
      'popup' => FALSE,
    ],
    'linkedin' => [
      'share_url' => 'https://www.linkedin.com/shareArticle?mini=true&url=[rrssb:url]&title=[rrssb:title]',
      'follow_url' => 'https://www.linkedin.com/in/[rrssb:username]',
      'color' => '#007bb6',
      'color_hover' => '#005983',
    ],
    'pinterest' => [
      'share_url' => 'https://pinterest.com/pin/create/link/?url=[rrssb:url]&media=[rrssb:image]&description=[rrssb:title]',
      'follow_url' => 'https://www.pinterest.com/[rrssb:username]',
      'color' => '#b81621',
      'color_hover' => '#8a1119',
    ],
    'phone' => [
      'follow_url' => 'tel:[rrssb:username]',
      'color' => '#33cc33',
      'color_hover' => '#29a329',
      'text' => '[rrssb:username]',
    ],
    'pocket' => [
      'share_url' => 'https://getpocket.com/save?url=[rrssb:url]',
      'color' => '#ed4054',
      'color_hover' => '#e4162d',
    ],
    'print' => [
      'share_url' => 'javascript:window.print()',
      'color' => '#8d98a2',
      'color_hover' => '#717f8b',
      'popup' => FALSE,
    ],
    'reddit' => [
      'share_url' => 'https://www.reddit.com/submit?url=[rrssb:url]&title=[rrssb:title]',
      'follow_url' => 'https://www.reddit.com/user/[rrssb:username]',
      'color' => '#8bbbe3',
      'color_hover' => '#62a3d9',
    ],
    'tumblr' => [
      'share_url' => 'https://tumblr.com/share?s=&amp;v=3&t=[rrssb:title]&u=[rrssb:url]',
      'follow_url' => 'https://[rrssb:username].tumblr.com/',
      'color' => '#32506d',
      'color_hover' => '#22364a',
    ],
    'twitter' => [
      'share_url' => 'https://twitter.com/intent/tweet?text=[rrssb:title]&url=[rrssb:url]',
      'follow_url' => 'https://twitter.com/[rrssb:username]',
      'color' => '#26c4f1',
      'color_hover' => '#0eaad6',
    ],
    'vk' => [
      'share_url' => 'https://vk.com/share.php?url=[rrssb:url]',
      'color' => '#4d71a9',
      'color_hover' => '#3d5a86',
      'text' => 'vk.com',
    ],
    'whatsapp' => [
      'share_url' => 'whatsapp://send?text=[rrssb:title] [rrssb:url]',
      'color' => '#43d854',
      'color_hover' => '#28c039',
    ],
    'youtube' => [
      'follow_url' => 'https://www.youtube.com/user/[rrssb:username]',
      'color' => '#df1c31',
      'color_hover' => '#b21627',
    ],
  ];

  return $buttons;
}
