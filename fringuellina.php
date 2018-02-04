<?php
//echo 'Its been a while since ive done php. Let me hide my gun first.';

class Fringuellina {
	public static $cakeRecipeName = "Cakes"; 

    public static function PrintPage(){

		if ($_GET['id'] && !empty($_GET['id'])) {
			Fringuellina::PrintCake();
			return;
		}

		if ($_GET['uid'] && !empty($_GET['uid'])) {
			Fringuellina::PrintUserCakes();
			return;
		}

        // Multiple pages
		$pageInterval = 100;
		$from = (isset($_GET["from"])) ? $_GET["from"] : 999;
		$to = $from+$pageInterval;
		$users = $GLOBALS['db']->fetchAll('SELECT * FROM users WHERE id >= ? AND id < ?', [$from, $to]);
		$groups = $GLOBALS["db"]->fetchAll("SELECT * FROM privileges_groups");

        // Print stuff
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper">';
		// Maintenance check
		P::MaintenanceStuff();
		// Print Success if set
		if (isset($_GET['s']) && !empty($_GET['s'])) {
			P::SuccessMessageStaccah($_GET['s']);
		}
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			P::ExceptionMessageStaccah($_GET['e']);
        }
        
		// Get values
		//$wm = current($GLOBALS['db']->fetch("SELECT value_int FROM system_settings WHERE name = 'website_maintenance'"));
		
		echo '<p align="center"><font size=5><i class="fa fa-birthday-cake"></i>	Cakes</font></p><br>';
		// Quick edit/silence/kick user button
		echo '<p align="center"><button type="button" class="btn btn-primary" data-toggle="modal" data-target="#quickLookupUserModal">Quick lookup user (username)</button>';
		echo '&nbsp;&nbsp; <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#quickLookupScoreidModal">Quick lookup cake (score_id)</button>';
		echo '</p>';
		// Users plays table
		echo '<table class="table table-striped table-hover table-50-center">
		<thead>
		<tr><th class="text-center"><i class="fa fa-user"></i>	ID</th><th class="text-center">Username</th><th class="text-center">Cakes</th><th class="text-center">Bad cakes</th><th class="text-center">Bad flags</th><th class="text-center">Status</th><th class="text-center">Actions</th></tr>
		</thead>
		<tbody>';
		foreach ($users as $user) {
			// Get allowed color/text
			$statusColor = "success";
			$statusText = "Ok";
			if (($user["privileges"] & Privileges::UserPublic) == 0 && ($user["privileges"] & Privileges::UserNormal) == 0) {
				// Not visible and not active, banned
				$statusColor = "danger";
				$statusText = "Banned";
			} else if (($user["privileges"] & Privileges::UserPublic) == 0 && ($user["privileges"] & Privileges::UserNormal) > 0) {
				// Not visible but active, restricted
				$statusColor = "warning";
				$statusText = "Restricted";
			} else if (($user["privileges"] & Privileges::UserPublic) > 0 && ($user["privileges"] & Privileges::UserNormal) == 0) {
				// Visible but not active, disabled (not supported yet)
				$statusColor = "default";
				$statusText = "Locked";
            }
            
            $cakes = current($GLOBALS['db']->fetch('SELECT COUNT(*) FROM cakes WHERE userid = ?', [$user["id"]]));

			$badCakes = current($GLOBALS['db']->fetch('SELECT COUNT(*) FROM cakes WHERE userid = ? AND detected NOT LIKE ?', [$user["id"], '[]']));
			$badFlags = current($GLOBALS['db']->fetch('SELECT COUNT(*) FROM cakes WHERE userid = ? AND flags NOT IN (0,4)', [$user["id"]]));

			// Print row
			echo '<tr>';
			echo '<td><p class="text-center">'.$user['id'].'</p></td>';
			echo '<td><p class="text-center"><b>'.$user['username'].'</b></p></td>';
			echo '<td><p class="text-center">'.$cakes.'</p></td>';
			echo '<td><p class="text-center">'.$badCakes.'</p></td>';
			echo '<td><p class="text-center">'.$badFlags.'</p></td>';
            echo '<td><p class="text-center"><span class="label label-'.$statusColor.'">'.$statusText.'</span></p></td>';
            echo '<td><p class="text-center"><div class="btn-group">';
			echo '<a title="Edit user" class="btn btn-xs btn-primary" href="index.php?p=128&uid='.$user['id'].'"><span class="glyphicon glyphicon-pencil"></span></a>';
			if (hasPrivilege(Privileges::AdminBanUsers)) {
				if (isBanned($user["id"])) {
					echo '<a title="Unban user" class="btn btn-xs btn-success" onclick="sure(\'submit.php?action=banUnbanUser&id='.$user['id'].'\')"><span class="glyphicon glyphicon-thumbs-up"></span></a>';
				} else {
					echo '<a title="Ban user" class="btn btn-xs btn-warning" onclick="sure(\'submit.php?action=banUnbanUser&id='.$user['id'].'\')"><span class="glyphicon glyphicon-thumbs-down"></span></a>';
				}
				if (isRestricted($user["id"])) {
					echo '<a title="Remove restrictions" class="btn btn-xs btn-success" onclick="sure(\'submit.php?action=restrictUnrestrictUser&id='.$user['id'].'\')"><span class="glyphicon glyphicon-ok-circle"></span></a>';
				} else {
					echo '<a title="Restrict user" class="btn btn-xs btn-warning" onclick="sure(\'submit.php?action=restrictUnrestrictUser&id='.$user['id'].'\')"><span class="glyphicon glyphicon-remove-circle"></span></a>';
				}
			}
			echo '</div>';
			echo '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
		echo '<p align="center"><a href="index.php?p=128&from='.($from-($pageInterval+1)).'">< Previous page</a> | <a href="index.php?p=128&from='.($to).'">Next page ></a></p>';
		echo '</div>';



		Fringuellina::PrintLookupUserModule();
		Fringuellina::PrintLookupCakeModule();
	}
	
	public static function PrintUserCakes(){
		// Print stuff
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper">';
		// Maintenance check
		P::MaintenanceStuff();
		// Print Success if set
		if (isset($_GET['s']) && !empty($_GET['s'])) {
			P::SuccessMessageStaccah($_GET['s']);
		}
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			P::ExceptionMessageStaccah($_GET['e']);
		}

		$uid = $_GET['uid'];

		$user = $GLOBALS['db']->fetch('SELECT * FROM users WHERE id = ?', [$uid]);

		$statusColor = "success";
		$statusText = "Ok";
		if (($user["privileges"] & Privileges::UserPublic) == 0 && ($user["privileges"] & Privileges::UserNormal) == 0) {
			// Not visible and not active, banned
			$statusColor = "danger";
			$statusText = "Banned";
		} else if (($user["privileges"] & Privileges::UserPublic) == 0 && ($user["privileges"] & Privileges::UserNormal) > 0) {
			// Not visible but active, restricted
			$statusColor = "warning";
			$statusText = "Restricted";
		} else if (($user["privileges"] & Privileges::UserPublic) > 0 && ($user["privileges"] & Privileges::UserNormal) == 0) {
			// Visible but not active, disabled (not supported yet)
			$statusColor = "default";
			$statusText = "Locked";
		}

		$cakes = $GLOBALS['db']->fetchAll('SELECT * FROM cakes WHERE userid = ?', [$uid]);

		$badCakes = current($GLOBALS['db']->fetch('SELECT COUNT(*) FROM cakes WHERE userid = ? AND detected NOT LIKE ?', [$uid, '[]']));
		$badFlags = current($GLOBALS['db']->fetch('SELECT COUNT(*) FROM cakes WHERE userid = ? AND flags NOT IN (0,4)', [$uid]));


		echo '<div class="row">';
		printAdminPanel('primary', 'fa fa-birthday-cake fa-5x', count($cakes), 'Cakes');
		printAdminPanel('red', 'fa fa-thumbs-down fa-5x', $badCakes, 'Bad cakes');
		printAdminPanel('yellow', 'fa fa-flag fa-5x', $badFlags, 'Bad flags');
		printAdminPanel($statusColor, 'fa fa-id-card fa-5x', $statusText, 'Status');
		echo '</div>';

		echo '<p align="center"><font size=5><i class="fa fa-birthday-cake"></i>	'.$user['username'].'\'s Cakes</font></p><br>';

		echo '<table class="table table-striped table-hover table-50-center">
		<thead>
		<tr><th class="text-center"><i class="fa fa-birthday-cake"></i>	Cake ID</th><th class="text-center">Score ID</th><th class="text-center">Cake ingredients</th><th class="text-center">Cake comment</th><th class="text-center">Flags</th><th class="text-center">Cake rating</th><th class="text-center">Actions</th></tr>
		</thead>
		<tbody>';
		foreach ($cakes as $cake) {
			echo '<td><p class="text-center">'.$cake['id'].'</p></td>';
			echo '<td><p class="text-center">'.$cake['score_id'].'</p></td>';
			echo '<td><p class="text-center"><a href="index.php?p=128&id='.$cake['id'].'" type="button" class="btn btn-primary">Check cake</a></p></td>';
			echo '<td><p class="text-center">'.$cake['detected'].'</p></td>';
			echo '<td><p class="text-center">'.$cake['flags'].'</p></td>';
			echo '<td><p class="text-center">placeholder</p></td>';
			echo '<td><p class="text-center">placeholder</p></td>';

			echo '</tr>';
		}
		echo '</tbody></table>';
		echo '</div></div>';
	}

	public static function PrintCake(){
		// Print stuff
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper">';
		// Maintenance check
		P::MaintenanceStuff();
		// Print Success if set
		if (isset($_GET['s']) && !empty($_GET['s'])) {
			P::SuccessMessageStaccah($_GET['s']);
		}
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			P::ExceptionMessageStaccah($_GET['e']);
		}

		$id = $_GET['id'];

		$cake = $GLOBALS['db']->fetch('SELECT * FROM cakes WHERE id = ?', [$id]);
		$user = $GLOBALS['db']->fetch('SELECT * FROM users WHERE id = ?', [$cake['userid']]);

		$flags = Fringuellina::makeFlagString($cake['flags']);

		$beatmap_md5 = $GLOBALS['db']->fetch('SELECT beatmap_md5 FROM scores WHERE id = ?', [$cake['score_id']])['beatmap_md5'];

		$beatmap = $GLOBALS['db']->fetch('SELECT beatmap_id,beatmapset_id FROM beatmaps WHERE beatmap_md5 = ?', [$beatmap_md5]);

		$pl = json_decode($cake['processes'], true);

		echo '<p align="center"><font size="5"><i class="fa fa-birthday-cake"></i>	Edit cake#'.$id.'</font></p>';
		echo '<table class="table table-striped table-hover table-center"><tbody>';

		echo '<form id="system-settings-form" action="submit.php" method="POST"></form>';

		echo '<tr>
		<td width=1>ID</td>
		<td><p class="text-center"><input type="number" name="id" class="form-control" value="'.$id.'" readonly=""></p></td>
		</tr>';

		echo '<tr>
		<td>Username</td>
		<td><p class="text-center"><input type="text" name="username" class="form-control" value="'.$user['username'].'" readonly=""></p></td>
		</tr>';

		echo '<tr>
		<td>Score ID</td>
		<td><p class="text-center"><input type="number" name="scoreid" class="form-control" value="'.$cake['score_id'].'" readonly=""></p>
		<div class="text-center">
			<a href="'.Fringuellina::getBeatmapUrl($beatmap['beatmap_id']).'" type="button" class="btn btn-success">Download Beatmap</a>
			<a href="http://'.Fringuellina::getMainDomain().'/web/replays/'.$cake['score_id'].'" type="button" class="btn btn-primary">Download Replay</a>
		</div>
		</tr>';

		echo '<tr>
		<td>Flags</td>
		<td><p class="text-center"><input type="text" name="flags" class="form-control" value="'.$flags.'" readonly=""></p></td>
		</tr>';
		
		echo '<tr>
		<td>Cake Ingredients</td>
		<td>';

		echo '<a class="btn btn-success btn-block" data-toggle="collapse" href="#collapsePList" aria-expanded="false" aria-controls="collapsePList">Show</a>';

		echo '<div class="collapse" id="collapsePList">';
		foreach ($pl as $item){
			if ($item['hash'] == null && $item['path'] == null && $item['title'] == null)
				if (in_array($item['file'], ['svchost', 'SearchIndexer', 'chrome', 'smss', 'SearchUI', 'csrss', 'RuntimeBroker', 'spoolsv', 'SettingSyncHost', 'Memory Compression', 'conhost', 'lsass', 'conhost', 'dwm', 'rundll32', 'dllhost', 'Idle']))
					continue;

			$c = "primary";
			//Do some check to see if it is type WARNING (flagged)

			echo '<a class="btn btn-block btn-'.$c.'">'.$item['file'].'<br>'.$item['hash'].'<br>'.$item['path'].'<br>'.$item['title'].'<br></a>';
		}
		echo '</div>';

		echo '</td>
		</tr>';

		echo '</tbody></table>';

		echo '</div></div>';
	}

    public static function PrintInfoPage(){
		
    }

    public static function PrintCakesSummary(){
		// Print stuff
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper">';
		// Maintenance check
		P::MaintenanceStuff();
		// Print Success if set
		if (isset($_GET['s']) && !empty($_GET['s'])) {
			P::SuccessMessageStaccah($_GET['s']);
		}
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			P::ExceptionMessageStaccah($_GET['e']);
        }
    }

    public static function PrintEditCake(){

    }

    public static function RAPButton(){
		echo '<li><a href="index.php?p=128"><i class="fa fa-birthday-cake"></i>	Cakes</a></li>';
    }

    public static function RAPCakesListButton(){
		echo '<li><a href="index.php?p=130"><i class="fa fa-book"></i>	Cake recipes</a></li>';
    }

	//submit.php
	//toggleCake
    public static function ToggleCake(){

    }
	//removeCake
	public static function RemoveCake(){

    }
	//saveCake
    public static function EditCake(){

	}

	//TODO; Make seperate php file to redirect to correct page with custom lookups etc.
	public static function PrintLookupUserModule(){
		echo '<div class="modal fade" id="quickLookupUserModal" tabindex="-1" role="dialog" aria-labelledby="quickLookupUserModal">
		<div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title" id="quickLookupUserModal">Quick lookup user</h4>
		</div>
		<div class="modal-body">
		<p>
		<form id="quick-edit-user-form" action="submit.php" method="POST">
		<input name="action" value="quickEditUser" hidden>
		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-user" aria-hidden="true"></span></span>
		<input type="text" name="u" class="form-control" placeholder="Username" aria-describedby="basic-addon1" required>
		</div>
		</form>
		</p>
		</div>
		<div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		<button type="submit" form="quick-edit-user-form" class="btn btn-primary">Check</button>
		</div>
		</div>
		</div>
		</div>';
	}

	public static function PrintLookupCakeModule(){
		echo '<div class="modal fade" id="quickLookupScoreidModal" tabindex="-1" role="dialog" aria-labelledby="quickLookupScoreidModal">
		<div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title" id="quickLookupScoreidModal">Quick lookup cake</h4>
		</div>
		<div class="modal-body">
		<p>
		<form id="quick-edit-user-form" action="submit.php" method="POST">
		<input name="action" value="quickEditUser" hidden>
		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-tag" aria-hidden="true"></span></span>
		<input type="text" onkeypress="return event.charCode >= 48 && event.charCode <= 57" name="u" class="form-control" placeholder="Score ID" aria-describedby="basic-addon1" required>
		</div>
		</form>
		</p>
		</div>
		<div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		<button type="submit" form="quick-edit-user-form" class="btn btn-primary">Check</button>
		</div>
		</div>
		</div>
		</div>';
	}

	public static function getMainDomain(){
		$host_names = explode(".", $_SERVER['SERVER_NAME']);
		unset($host_names[0]);
		$main_domain = implode(".", $host_names);

		return $main_domain;
	}

	public static function getBeatmapUrl($id){
		return "http://".Fringuellina::getMainDomain()."/b/".$id;
	}

	const IGNORE = BadFlags::CLEAN | BadFlags::INCORRECT_MOD;
	public static function makeFlagString($i){
		$flags = [];

		$ref = new ReflectionClass("BadFlags");
		$arr = $ref->getConstants();

		foreach ($arr as $flag)
		{
			if (($i & $flag) != 0)
				array_push($flags, array_search($flag, $arr));
		}

		unset($ref);

		return implode(" | ", $flags);
	}
}

class BadFlags{
	const CLEAN = 0;
	const SPEED = 1 << 1;
	const INCORRECT_MOD = 1 << 2;
	const MULTIPLE_OSU_CLIENTS = 1 << 3;
	const CHECKSUM_FAIL = 1 << 4;
	const FLASHLIGHT_CHECKSUM_FAIL = 1 << 5;
	const OSU_CHECKSUM = 1 << 6;
	const MISSING_PL = 1 << 7;
	const FLASHLIGHT_IMAGE = 1 << 8;
	const SPINNER = 1 << 9;
	const TRANSPARENT_WINDOW = 1 << 10;
	const FAST_PRESS = 1 << 11;
	const RAW_MOUSE_DISCREPANCY = 1 << 12;
	const RAW_KEYBOARD_DISCREPANCY = 1 << 13;
}
?>