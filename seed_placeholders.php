<?php
$pdo=new PDO('mysql:host=localhost;dbname=monsqcgn_djsar;charset=utf8mb4','monsqcgn_ciberula','Espora1740.');
for($i=1;$i<=20;$i++){
 $name='Placeholder DJ '.$i;
 $slug='placeholder-dj-'.$i;
 $stmt=$pdo->prepare("INSERT INTO djs (slug,name,genre,location_city,location_province,bio,created_at) VALUES (:slug,:name,:genre,:city,:prov,:bio,NOW())");
 try{
  $stmt->execute([
   ':slug'=>$slug,
   ':name'=>$name,
   ':genre'=>'House, Techno',
   ':city'=>'Buenos Aires',
   ':prov'=>'Buenos Aires',
   ':bio'=>'Perfil de prueba #'.$i
  ]);
  echo "added $i\n";
 }catch(PDOException $e){ echo 'error '.$i.': '.$e->getMessage()."\n"; }
}
?>
