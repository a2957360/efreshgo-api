<?php
include("../include/sql.php");
include("../include/conf/config.php");

function checkUser($pdo,$userId){
  $stmt = $pdo->prepare("SELECT `userState` From `userTable` WHERE `userId` = '$userId'");
  $stmt->execute();
  if($stmt != null){
    while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
      //黑名单返回
      if($row['userState'] == 2){
        return 0;
      }
      return 1;
    }
    return 0;
  }else{

  }
}
