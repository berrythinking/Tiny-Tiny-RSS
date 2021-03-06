<?php
	function module_pref_users($link) {

		global $access_level_names;

		if (!SINGLE_USER_MODE && $_SESSION["access_level"] < 10) { 
			print __("Your access level is insufficient to open this tab.");
			return;
		}

		$subop = $_REQUEST["subop"];

		if ($subop == "user-details") {

			$uid = sprintf("%d", $_REQUEST["id"]);

			print "<div id=\"infoBoxTitle\">".__('User details')."</div>";

			print "<div class='infoBoxContents'>";

			$result = db_query($link, "SELECT login,
				".SUBSTRING_FOR_DATE."(last_login,1,16) AS last_login,
				access_level,
				(SELECT COUNT(int_id) FROM ttrss_user_entries 
					WHERE owner_uid = id) AS stored_articles,
				".SUBSTRING_FOR_DATE."(created,1,16) AS created
				FROM ttrss_users 
				WHERE id = '$uid'");
				
			if (db_num_rows($result) == 0) {
				print "<h1>".__('User not found')."</h1>";
				return;
			}
			
			// print "<h1>User Details</h1>";

			$login = db_fetch_result($result, 0, "login");

			print "<table width='100%'>";

			$last_login = date(get_pref($link, 'LONG_DATE_FORMAT'),
				strtotime(db_fetch_result($result, 0, "last_login")));

			$created = date(get_pref($link, 'LONG_DATE_FORMAT'),
				strtotime(db_fetch_result($result, 0, "created")));

			$access_level = db_fetch_result($result, 0, "access_level");
			$stored_articles = db_fetch_result($result, 0, "stored_articles");

			print "<tr><td>".__('Registered')."</td><td>$created</td></tr>";
			print "<tr><td>".__('Last logged in')."</td><td>$last_login</td></tr>";

			$result = db_query($link, "SELECT COUNT(id) as num_feeds FROM ttrss_feeds
				WHERE owner_uid = '$uid'");

			$num_feeds = db_fetch_result($result, 0, "num_feeds");

			print "<tr><td>".__('Subscribed feeds count')."</td><td>$num_feeds</td></tr>";
	
			print "</table>";

			print "<h1>".__('Subscribed feeds')."</h1>";

			$result = db_query($link, "SELECT id,title,site_url FROM ttrss_feeds
				WHERE owner_uid = '$uid' ORDER BY title");

			print "<ul class=\"userFeedList\">";

			$row_class = "odd";

			while ($line = db_fetch_assoc($result)) {

				$icon_file = ICONS_URL."/".$line["id"].".ico";

				if (file_exists($icon_file) && filesize($icon_file) > 0) {
					$feed_icon = "<img class=\"tinyFeedIcon\" src=\"$icon_file\">";
				} else {
					$feed_icon = "<img class=\"tinyFeedIcon\" src=\"images/blank_icon.gif\">";
				}

				print "<li class=\"$row_class\">$feed_icon&nbsp;<a href=\"".$line["site_url"]."\">".$line["title"]."</a></li>";

				$row_class = toggleEvenOdd($row_class);

			}

			if (db_num_rows($result) < $num_feeds) {
				// FIXME - add link to show ALL subscribed feeds here somewhere
				print "<li><img 
					class=\"tinyFeedIcon\" src=\"images/blank_icon.gif\">&nbsp;...</li>";
			}
			
			print "</ul>";

			print "<div align='center'>
				<button onclick=\"closeInfoBox()\">".__("Close this window").
				"</button></div>";

			print "</div>";

			return;
		}

		if ($subop == "edit") {

			$id = db_escape_string($_REQUEST["id"]);

			print "<div id=\"infoBoxTitle\">".__('User Editor')."</div>";
			
			print "<div class=\"infoBoxContents\">";

			print "<form id=\"user_edit_form\" onsubmit='return false'>";

			print "<input type=\"hidden\" name=\"id\" value=\"$id\">";
			print "<input type=\"hidden\" name=\"op\" value=\"pref-users\">";
			print "<input type=\"hidden\" name=\"subop\" value=\"editSave\">";

			$result = db_query($link, "SELECT * FROM ttrss_users WHERE id = '$id'");

			$login = db_fetch_result($result, 0, "login");
			$access_level = db_fetch_result($result, 0, "access_level");
			$email = db_fetch_result($result, 0, "email");

			$sel_disabled = ($id == $_SESSION["uid"]) ? "disabled" : "";

			print "<div class=\"dlgSec\">".__("User")."</div>";
			print "<div class=\"dlgSecCont\">";

			if ($sel_disabled) {
				print "<input type=\"hidden\" name=\"login\" value=\"$login\">";
				print "<input size=\"30\" style=\"font-size : 16px\" 
					onkeypress=\"return filterCR(event, userEditSave)\" $sel_disabled
					value=\"$login\">";
			} else {
				print "<input size=\"30\" style=\"font-size : 16px\" 
					onkeypress=\"return filterCR(event, userEditSave)\" $sel_disabled
					name=\"login\" value=\"$login\">";
			}

			print "</div>";

			print "<div class=\"dlgSec\">".__("Authentication")."</div>";
			print "<div class=\"dlgSecCont\">";

			print __('Access level: ') . " ";

			if (!$sel_disabled) {
				print_select_hash("access_level", $access_level, $access_level_names, 
					$sel_disabled);
			} else {
				print_select_hash("", $access_level, $access_level_names, 
					$sel_disabled);
				print "<input type=\"hidden\" name=\"access_level\" value=\"$access_level\">";
			}

			print "<br/>";

			print __('Change password to') .
				" <input size=\"20\" onkeypress=\"return filterCR(event, userEditSave)\"
				name=\"password\">";

			print "</div>";

			print "<div class=\"dlgSec\">".__("Options")."</div>";
			print "<div class=\"dlgSecCont\">";

			print __('E-mail: ').
				" <input size=\"30\" name=\"email\" onkeypress=\"return filterCR(event, userEditSave)\"
				value=\"$email\">";

			print "</div>";

			print "</table>";

			print "</form>";

			print "<div class=\"dlgButtons\">
				<button onclick=\"return userEditSave()\">".
					__('Save')."</button>
				<button onclick=\"return userEditCancel()\">".
					__('Cancel')."</button></div>";

			print "</div>";

			return;
		}

		if ($subop == "editSave") {
	
			if (!WEB_DEMO_MODE && $_SESSION["access_level"] >= 10) {

				$login = db_escape_string(trim($_REQUEST["login"]));
				$uid = db_escape_string($_REQUEST["id"]);
				$access_level = (int) $_REQUEST["access_level"];
				$email = db_escape_string(trim($_REQUEST["email"]));
				$password = db_escape_string(trim($_REQUEST["password"]));

				if ($password) {
					$pwd_hash = encrypt_password($password, $login);
					$pass_query_part = "pwd_hash = '$pwd_hash', ";					
					print_notice(T_sprintf('Changed password of user <b>%s</b>.', $login));
				} else {
					$pass_query_part = "";
				}

				db_query($link, "UPDATE ttrss_users SET $pass_query_part login = '$login', 
					access_level = '$access_level', email = '$email' WHERE id = '$uid'");

			}
		} else if ($subop == "remove") {

			if ($_SESSION["access_level"] >= 10) {

				$ids = split(",", db_escape_string($_REQUEST["ids"]));

				foreach ($ids as $id) {
					if ($id != $_SESSION["uid"]) {
						db_query($link, "DELETE FROM ttrss_tags WHERE owner_uid = '$id'");
						db_query($link, "DELETE FROM ttrss_feeds WHERE owner_uid = '$id'");
						db_query($link, "DELETE FROM ttrss_users WHERE id = '$id'");
					}
				}
			}
		} else if ($subop == "add") {
		
			if ($_SESSION["access_level"] >= 10) {

				$login = db_escape_string(trim($_REQUEST["login"]));
				$tmp_user_pwd = make_password(8);
				$pwd_hash = encrypt_password($tmp_user_pwd, $login);

				$result = db_query($link, "SELECT id FROM ttrss_users WHERE 
					login = '$login'");

				if (db_num_rows($result) == 0) {

					db_query($link, "INSERT INTO ttrss_users 
						(login,pwd_hash,access_level,last_login,created)
						VALUES ('$login', '$pwd_hash', 0, null, NOW())");
	
	
					$result = db_query($link, "SELECT id FROM ttrss_users WHERE 
						login = '$login' AND pwd_hash = '$pwd_hash'");
	
					if (db_num_rows($result) == 1) {
	
						$new_uid = db_fetch_result($result, 0, "id");
	
						print_notice(T_sprintf("Added user <b>%s</b> with password <b>%s</b>", 
							$login, $tmp_user_pwd));
	
						initialize_user($link, $new_uid);
	
					} else {
					
						print_warning(T_sprintf("Could not create user <b>%s</b>", $login));
	
					}
				} else {
					print_warning(T_sprintf("User <b>%s</b> already exists.", $login));
				}
			} 
		} else if ($subop == "resetPass") {

			if (!WEB_DEMO_MODE && $_SESSION["access_level"] >= 10) {

				$uid = db_escape_string($_REQUEST["id"]);

				$result = db_query($link, "SELECT login,email 
					FROM ttrss_users WHERE id = '$uid'");

				$login = db_fetch_result($result, 0, "login");
				$email = db_fetch_result($result, 0, "email");
				$tmp_user_pwd = make_password(8);
				$pwd_hash = encrypt_password($tmp_user_pwd, $login);

				db_query($link, "UPDATE ttrss_users SET pwd_hash = '$pwd_hash'
					WHERE id = '$uid'");

				print_notice(T_sprintf("Changed password of user <b>%s</b>
					 to <b>%s</b>", $login, $tmp_user_pwd));

				if ($email) {
					print_notice(T_sprintf("Notifying <b>%s</b>.", $email));

					require_once "lib/MiniTemplator.class.php";

					$tpl = new MiniTemplator;

					$tpl->readTemplateFromFile("templates/resetpass_template.txt");

					$tpl->setVariable('LOGIN', $login);
					$tpl->setVariable('NEWPASS', $tmp_user_pwd);

					$tpl->addBlock('message');

					$message = "";

					$tpl->generateOutputToString($message);

					$mail = new PHPMailer();

					$mail->PluginDir = "lib/phpmailer/";
					$mail->SetLanguage("en", "lib/phpmailer/language/");

					$mail->CharSet = "UTF-8";

					$mail->From = DIGEST_FROM_ADDRESS;
					$mail->FromName = DIGEST_FROM_NAME;
					$mail->AddAddress($email, $login);

					if (DIGEST_SMTP_HOST) {
						$mail->Host = DIGEST_SMTP_HOST;
						$mail->Mailer = "smtp";
						$mail->SMTPAuth = DIGEST_SMTP_LOGIN != '';
						$mail->Username = DIGEST_SMTP_LOGIN;
						$mail->Password = DIGEST_SMTP_PASSWORD;
					}

					$mail->IsHTML(false);
					$mail->Subject = __("[tt-rss] Password change notification");
					$mail->Body = $message;

					$rc = $mail->Send();

					if (!$rc) print_error($mail->ErrorInfo);

/*					mail("$login <$email>", "Password reset notification",
						"Hi, $login.\n".
						"\n".
						"Your password for this TT-RSS installation was reset by".
							" an administrator.\n".
						"\n".
						"Your new password is $tmp_user_pwd, please remember".
							" it for later reference.\n".
						"\n".
						"Sincerely, TT-RSS Mail Daemon.", "From: " . MAIL_FROM); */
				}
					
				print "</div>";				

			}
		}

		set_pref($link, "_PREFS_ACTIVE_TAB", "userConfig");

		$user_search = db_escape_string($_REQUEST["search"]);

		if (array_key_exists("search", $_REQUEST)) {
			$_SESSION["prefs_user_search"] = $user_search;
		} else {
			$user_search = $_SESSION["prefs_user_search"];
		}

		print "<div style='float : right'>
			<input id=\"user_search\" size=\"20\" type=\"search\"
				onfocus=\"javascript:disableHotkeys();\" 
				onblur=\"javascript:enableHotkeys();\"
				onchange=\"javascript:updateUsersList()\" value=\"$user_search\">
			<button onclick=\"javascript:updateUsersList()\">".
				__('Search')."</button>
			</div>";

		$sort = db_escape_string($_REQUEST["sort"]);

		if (!$sort || $sort == "undefined") {
			$sort = "login";
		}

		print "<button onclick=\"javascript:addUser()\">".__('Create user')."</button>";

		print "
			<button onclick=\"javascript:selectedUserDetails()\">".
			__('Details')."</button>
			<button onclick=\"javascript:editSelectedUser()\">".
			__('Edit')."</button>
			<button onclick=\"javascript:removeSelectedUsers()\">".
			__('Remove')."</button>
			<button onclick=\"javascript:resetSelectedUserPass()\">".
			__('Reset password')."</button>";

		print "</div>";

		if ($user_search) {

			$user_search = split(" ", $user_search);
			$tokens = array();

			foreach ($user_search as $token) {			
				$token = trim($token);
				array_push($tokens, "(UPPER(login) LIKE UPPER('%$token%'))");
			}

			$user_search_query = "(" . join($tokens, " AND ") . ") AND ";

		} else {
			$user_search_query = "";
		}

		$result = db_query($link, "SELECT 
				id,login,access_level,email,
				".SUBSTRING_FOR_DATE."(last_login,1,16) as last_login,
				".SUBSTRING_FOR_DATE."(created,1,16) as created
			FROM 
				ttrss_users
			WHERE
				$user_search_query
				id > 0
			ORDER BY $sort");

		if (db_num_rows($result) > 0) {

//		print "<div id=\"infoBoxShadow\"><div id=\"infoBox\">PLACEHOLDER</div></div>";

		print "<p><table width=\"100%\" cellspacing=\"0\" 
			class=\"prefUserList\" id=\"prefUserList\">";

		print "<tr><td class=\"selectPrompt\" colspan=\"8\">
				".__('Select:')." 
					<a href=\"javascript:selectPrefRows('user', true)\">".__('All')."</a>,
					<a href=\"javascript:selectPrefRows('user', false)\">".__('None')."</a>
				</td</tr>";

		print "<tr class=\"title\">
					<td align='center' width=\"5%\">&nbsp;</td>
					<td width=''><a href=\"javascript:updateUsersList('login')\">".__('Login')."</a></td>
					<td width='20%'><a href=\"javascript:updateUsersList('access_level')\">".__('Access Level')."</a></td>
					<td width='20%'><a href=\"javascript:updateUsersList('created')\">".__('Registered')."</a></td>
					<td width='20%'><a href=\"javascript:updateUsersList('last_login')\">".__('Last login')."</a></td></tr>";
		
		$lnum = 0;
		
		while ($line = db_fetch_assoc($result)) {

			$class = ($lnum % 2) ? "even" : "odd";

			$uid = $line["id"];
			$edit_uid = $_REQUEST["id"];

			if ($subop == "edit" && $uid != $edit_uid) {
				$class .= "Grayed";
				$this_row_id = "";
			} else {
				$this_row_id = "id=\"UMRR-$uid\"";
			}		
			
			print "<tr class=\"$class\" $this_row_id>";

			$line["login"] = htmlspecialchars($line["login"]);

#			$line["last_login"] = date(get_pref($link, 'SHORT_DATE_FORMAT'),
#				strtotime($line["last_login"]));

			if (get_pref($link, 'HEADLINES_SMART_DATE')) {
				$line["last_login"] = smart_date_time(strtotime($line["last_login"]));
				$line["created"] = smart_date_time(strtotime($line["created"]));
			} else {
				$line["last_login"] = date(get_pref($link, 'SHORT_DATE_FORMAT'),
					strtotime($line["last_login"]));
				$line["created"] = date(get_pref($link, 'SHORT_DATE_FORMAT'),
					strtotime($line["created"]));
			}				

			print "<td align='center'><input onclick='toggleSelectPrefRow(this, \"user\");' 
				type=\"checkbox\" id=\"UMCHK-$uid\"></td>";

			$onclick = "onclick='editUser($uid)' title='".__('Click to edit')."'";

			print "<td $onclick>" . $line["login"] . "</td>";		

			if (!$line["email"]) $line["email"] = "&nbsp;";

			print "<td $onclick>" .	$access_level_names[$line["access_level"]] . "</td>";	
			print "<td $onclick>" . $line["created"] . "</td>";		
			print "<td $onclick>" . $line["last_login"] . "</td>";		
		
			print "</tr>";

			++$lnum;
		}

		print "</table>";

		} else {
			print "<p>";
			if (!$user_search) {
				print_warning(__('No users defined.'));
			} else {
				print_warning(__('No matching users found.'));
			}
			print "</p>";

		}

	}
?>
