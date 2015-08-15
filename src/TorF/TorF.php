<?php

namespace TorF;

use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\utils\Config;
use pocketmine\command\ConsoleCommandSender;

class TorF extends PluginBase implements Listener{

	public function onEnable(){
		if(!file_exists($this->getDataFolder())) @mkdir($this->getDataFolder(), 0755, true);
		$this->getServer()->getPluginManager()->registerEvents($this,$this);
		$this->users = [];
	}

	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		if(strtolower($command->getName()) == "tf"){
			if(!isset($args[0])) return false;
			switch($args[0]){
				case "start":  ///投票開始コマンド処理
					if(!isset($args[1])){//banする人の名前がなかったら
						$sender->sendMessage("§c[TF] banする人の名前を入力してください。");
					}elseif(!isset($args[2])){//理由がなかったら
						$sender->sendMessage("§c[TF] 理由も入力してください。");
					}elseif(isset($start)){//作業中だったら
						$sender->sendMessage("§c[TF] ただいま投票作業中です。");
					}else{
						$this->votePname = $args[1];//banされる人の名前
						$sender->getServer()->broadcastMessage("§c[TF] §a".$sender->getName()."氏から".$this->votePname."氏のban申請があります。");
						$sender->getServer()->broadcastMessage("§c[TF] §a理由は｢§b".$args[2]."§a｣だそうです。");
						$sender->getServer()->broadcastMessage("§c[TF] §aban投票を始めます。");
						$sender->getServer()->broadcastMessage("§c[TF] §a全員が投票するようお願いします。");
						$sender->getServer()->broadcastMessage("§c[TF] §a制限時間は§b3分§aです。");
						$this->users[$sender->getName()] = 1;//投票済み
						$this->reason = $args[2];//理由
						$this->false = 0;//反対数
						$this->start = 1;//投票開始
						$this->true = 1;//賛成数で投票開始する人は賛成
						$this->id = $this->getServer()->getScheduler()->scheduleDelayedTask(new FINISH($this), 1200*3)->getTaskId();//タスクの起動とidの取得
					}
					return true;
				case "true": ///ban賛成コマンド処理
					if(isset($this->users[$sender->getName()])){//投票済みだったら
						$sender->sendMessage("§c[TF] あなたは投票済みです。");
					}elseif(!isset($this->start)){//作業中だったら
						$sender->sendMessage("§c[TF] ただいま投票作業はありません。");
					}elseif($sender->getName() === $this->votePname){//banされる側だったら
						$sender->sendMessage("§c[TF] あなたはbanされる側です。");
					}else{
						$sender->getServer()->broadcastMessage("§c[TF] §b".$sender->getName()."さんが投票しました！");
						$this->users[$sender->getName()] = 1;//投票済み
						$this->true++;//賛成数増加
					}
					return true;
				case "false": ///ban反対処理
					if(!isset($this->start)){//作業中だったら
						$sender->sendMessage("§c[TF] ただいま投票作業はありません。");
					}elseif(isset($this->users[$sender->getName()])){//投票済みだったら
						$sender->sendMessage("§c[TF] あなたは投票済みです。");
					}else{
						$sender->getServer()->broadcastMessage("§c[TF] §b".$sender->getName()."さんが投票しました！");
						$this->users[$sender->getName()] = 1;//投票済み
						$this->false++;//反対数増加
					}
					return true;
				case "info"://投票説明処理
					if(!isset($this->start)){//作業中だったら
						$sender->sendMessage("§c[TF] ただいま投票作業はありません。");
					}else{
						$sender->sendMessage("§a[TF] §b名前:".$this->votePname." ban理由:".$this->reason);
						$Re = $sender->getName() === $this->votePname ? "§cあなたはbanされる側です。" : isset($this->users[$sender->getName()]) ? "§b投票済みです。" : "§c投票してください。";//banされる側か、または投票済みか
						$sender->sendMessage("§a[TF] ".$Re);
					}
					return true;
				case "fin":
					if(!isset($this->start)){//作業中だったら
						$sender->sendMessage("§c[TF] ただいま投票作業はありません。");
					}elseif($sender->isOp()){//opだったら
						$sender->getServer()->broadcastMessage("§c[TF] 投票は権限者の判断により中止されました。");
						if(isset($args[1])){//理由があったら
							$this->file($this->votePname. "のbanは「".$args[1]."」が理由で権限者による中断で終了");
							$sender->getServer()->broadcastMessage("§c[TF] §a中断理由「§b".$args[1]."§a」");
						}else{
							$this->file($this->votePname. "のbanは権限者による中断で終了");
						}
						$this->getServer()->getScheduler()->cancelTask($this->id);
						unset($this->votePname, $this->true, $this->false, $this->start, $this->reason, $this->id, $this->users);
					}else{
						$sender->sendMessage("§c[TF] あなたはオペレータ権限を持っていません！");
					}
					return true;
				default:
					return false;
			}
			return false;
		}
	}

	public function onJoin(PlayerJoinEvent $event){//プレイヤーが参加した
		$player = $event->getPlayer();
		if(isset($this->start)){//作業中だったら
			$player->sendMessage("§c[TF] §aただいまban投票作業中です。");
			$player->sendMessage("§c[TF] §aぜひご参加をよろしくおねがいします。");
			$player->sendMessage("§c[TF] §a内容は§b/tf info§aでお願いします。");
			return true;
		}
	}


	//投票が終わりました
	public function fini(){
		unset($this->start);
		$this->getServer()->broadcastMessage("§c[TF] §a3分経過ので結果を発表します。");
		$this->getServer()->broadcastMessage("§c[TF] §b".$this->true."§a対§c".$this->false."§aです。");
		if($this->true > $this->false){//賛成の方がおおかったら
			$this->getServer()->broadcastMessage("§c[TF] §aよって、ban投票が§b可決§aされました！");
			$this->getServer()->dispatchCommand(new ConsoleCommandSender, "ban ".$this->votePname);
			$this->file($this->votePname. "のban理由は".$this->reason."でban可決");
		}else{
			$this->getServer()->broadcastMessage("§c[TF] §aよって、ban投票が§c否決§aされました！");
			$this->file($this->votePname. "のban理由は".$this->reason."でban否決");
		}
		$this->getServer()->broadcastMessage("§c[TF] §b投票お疲れ様でした。");
		unset($this->true, $this->false, $this->votePname, $this->reason, $this->id, $this->users);
	}

	//ファイルに出力
	public function file($message){
		$now = \time();//今の時間
		$fp = fopen($this->getDataFolder() . "log.txt", 'ab');//ファイル開く
		fwrite($fp,  "[".\date("H:i:s", $now)."]".$message."\n");//ファイルに書き込む
		fclose($fp);//ファイル閉じる
	}


}