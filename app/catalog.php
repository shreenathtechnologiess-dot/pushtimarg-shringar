<?php
// app/catalog.php
if (!function_exists('slugify')) {
  function slugify($t){ $t=strtolower(trim($t)); $t=preg_replace('/[^a-z0-9]+/i','-',$t); return trim($t,'-'); }
}

function get_catalog(): array {
  $items = [
    ["name"=>"Blue Cotton","img"=>"blue-cotton.jpg","price"=>400,"category"=>"fabric","tags"=>["sale"]],
    ["name"=>"Golden Banarasi","img"=>"golden-banarasi.jpg","price"=>1200,"category"=>"fabric","tags"=>["featured"]],
    ["name"=>"Green Silk","img"=>"green-silk.jpg","price"=>1600,"category"=>"fabric","tags"=>[]],
    ["name"=>"Linen Blend","img"=>"linen-blend.jpg","price"=>2000,"category"=>"fabric","tags"=>["featured"]],
    ["name"=>"Purple Silk","img"=>"purple-silk.jpg","price"=>2800,"category"=>"fabric","tags"=>[]],
    ["name"=>"Premium Fabric","img"=>"premium-fabric.jpeg","price"=>3000,"category"=>"fabric","tags"=>["featured"]],
    ["name"=>"Maroon Paisley","img"=>"maroon-paisley.jpg","price"=>3500,"category"=>"fabric","tags"=>[]],
    ["name"=>"Orange Vastra","img"=>"orange-vastra.jpeg","price"=>4000,"category"=>"vastra","tags"=>["featured"]],
    ["name"=>"Royal Blue","img"=>"royal-blue.jpeg","price"=>4500,"category"=>"vastra","tags"=>["sale"]],
    ["name"=>"Cow Krishna Pichwai","img"=>"cow-krishna-pichwai.jpg","price"=>5000,"category"=>"pichwai","tags"=>[]],
    ["name"=>"Dancing Gopis Pichwai","img"=>"dancing-gopis-pichwai.jpg","price"=>5500,"category"=>"pichwai","tags"=>["featured"]],
    ["name"=>"Lotus Pond Pichwai","img"=>"lotus-pond-pichwai.jpg","price"=>6000,"category"=>"pichwai","tags"=>[]],
    ["name"=>"Radha Krishna Pichwai","img"=>"radha-krishna-pichwai.jpg","price"=>6500,"category"=>"pichwai","tags"=>[]],
    ["name"=>"Peacock Pichwai","img"=>"peacock-pichwai.jpg","price"=>7000,"category"=>"pichwai","tags"=>[]],
    ["name"=>"Tree Pichwai","img"=>"tree-pichwai.jpg","price"=>7500,"category"=>"pichwai","tags"=>[]],
    ["name"=>"Pink Floral Pichwai","img"=>"pink-floral-pichwai.jpg","price"=>8000,"category"=>"pichwai","tags"=>[]],
    ["name"=>"Shreenathji Elephant Pichwai","img"=>"shreenathji-elephant-pichwai.jpg","price"=>8500,"category"=>"pichwai","tags"=>[]],
    ["name"=>"Shreenathji Print","img"=>"shreenathji-print.jpg","price"=>9000,"category"=>"pichwai","tags"=>["sale"]],
  ];

  // add slug if missing
  foreach ($items as $k=>$p) {
    if (empty($items[$k]['slug'])) {
      $items[$k]['slug'] = slugify(pathinfo($p['img'], PATHINFO_FILENAME) ?: $p['name']);
    }
  }
  return $items;
}

function find_product(string $slug): ?array {
  foreach (get_catalog() as $p) if ($p['slug'] === $slug) return $p;
  return null;
}
