<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2014, Phoronix Media
	Copyright (C) 2008 - 2014, Michael Larabel

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
*/


class phoromatic_schedules implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Test Schedules';
	}
	public static function page_header()
	{
		return null;
	}
	public static function preload($PAGE)
	{
		return true;
	}
	public static function render_page_process($PATH)
	{
		if(isset($_POST['schedule_title']) && !empty($_POST['schedule_title']))
		{
			$title = $_POST['schedule_title'];
			$description = $_POST['schedule_description'];
			$pre_install_context = $_POST['pre_install_set_context'];
			$post_install_context = $_POST['post_install_set_context'];
			$pre_run_context = $_POST['pre_run_set_context'];
			$post_run_context = $_POST['post_run_set_context'];

			$system_all = $_POST['system_all'];
			$run_on_systems = $_POST['run_on_systems'];
			$run_on_groups = $_POST['run_on_groups'];

			$schedule_hour = $_POST['schedule_hour'];
			$schedule_minute = $_POST['schedule_minute'];
			$days_active = $_POST['days_active'];

			// TODO XXX: Validation of input

			do
			{
				$schedule_id = rand(10, 9999);
				$matching_schedules = phoromatic_server::$db->querySingle('SELECT ScheduleID FROM phoromatic_schedules WHERE AccountID = \'' . $_SESSION['AccountID'] . '\' AND ScheduleID = \'' . $schedule_id . '\'');
			}
			while(!empty($matching_schedules));

			do
			{
				$public_key = pts_strings::random_characters(12, true);;
				$matching_schedules = phoromatic_server::$db->querySingle('SELECT ScheduleID FROM phoromatic_schedules WHERE AccountID = \'' . $_SESSION['AccountID'] . '\' AND PublicKey = \'' . $public_key . '\'');
			}
			while(!empty($matching_schedules));

			$stmt = phoromatic_server::$db->prepare('INSERT INTO phoromatic_schedules (AccountID, ScheduleID, Title, Description, State, ActiveOn, RunAt, SetContextPreInstall, SetContextPostInstall, SetContextPreRun, SetContextPostRun, LastModifiedBy, LastModifiedOn, PublicKey) VALUES (:account_id, :schedule_id, :title, :description, :state, :active_on, :run_at, :context_pre_install, :context_post_install, :context_pre_run, :context_post_run, :modified_by, :modified_on, :public_key)');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$stmt->bindValue(':schedule_id', $schedule_id);
			$stmt->bindValue(':title', $title);
			$stmt->bindValue(':description', $description);
			$stmt->bindValue(':state', 1);
			$stmt->bindValue(':active_on', implode(',', $days_active));
			$stmt->bindValue(':run_at', $schedule_hour . ':' . $schedule_minute);
			$stmt->bindValue(':context_pre_install', $pre_install_context);
			$stmt->bindValue(':context_post_install', $post_install_context);
			$stmt->bindValue(':context_pre_run', $pre_run_context);
			$stmt->bindValue(':context_post_run', $post_run_context);
			$stmt->bindValue(':modified_by', $_SESSION['UserName']);
			$stmt->bindValue(':modified_on', phoromatic_server::current_time());
			$stmt->bindValue(':public_key', $public_key);
			$result = $stmt->execute();

			// self::$db->exec('CREATE TABLE phoromatic_schedules (AccountID TEXT UNIQUE, ScheduleID INTEGER UNIQUE, Title TEXT, Description TEXT, State INTEGER, ActiveOn TEXT, RunAt TEXT, SetContextPreInstall TEXT, SetContextPostInstall TEXT, SetContextPreRun TEXT, SetContextPostRun TEXT, LastModifiedBy TEXT, LastModifiedOn TEXT, PublicKey TEXT)');
		}


			echo phoromatic_webui_header_logged_in();

			$main = '<h1>Test Schedules</h1>
				<h2>Current Schedules</h2>';


			$main .= '<div class="pts_phoromatic_info_box_area">

				<div style="float: left; width: 100%;">
					<ul>
						<li><h1>Active Test Schedules</h1></li>';

					$stmt = phoromatic_server::$db->prepare('SELECT Title, ScheduleID, Description FROM phoromatic_schedules WHERE AccountID = :account_id AND State >= 1 ORDER BY Title ASC');
					$stmt->bindValue(':account_id', $_SESSION['AccountID']);
					$result = $stmt->execute();
					$row = $result->fetchArray();

					if($row == false)
					{
						$main .= '<li class="light" style="text-align: center;">No Schedules Found</li>';
					}
					else
					{
						do
						{
							$main .= '<a href="?schedules/' . $row['ScheduleID'] . '"><li>' . $row['Title'] . '<br /><em>' . $row['Description'] . '</em></li></a>';
						}
						while($row = $result->fetchArray());
					}


			$main .= '</ul>
				</div>
			</div>';

			$main .= '
			<hr />
			<h2>Create A Schedule</h2>
			<p>Account settings are system-wide, in cases where there are multiple individuals/accounts managing the same test systems and data.</p>';

			$main .= '<form action="?schedules/add" name="add_test" id="add_test" method="post" enctype="multipart/form-data" onsubmit="return validate_schedule();">
			<h3>Title</h3>
			<p><input type="text" name="schedule_title" /></p>
			<h3><em>Pre-Install Set Context Script:</em></h3>
			<p><input type="file" name="pre_install_set_context" /></p>
			<h3><em>Post-Install Set Context Script:</em></h3>
			<p><input type="file" name="post_install_set_context" /></p>
			<h3><em>Pre-Run Set Context Script:</em></h3>
			<p><input type="file" name="pre_run_set_context" /></p>
			<h3><em>Post-Run Set Context Script:</em></h3>
			<p><input type="file" name="post_run_set_context" /></p>
			<h3>System Targets:</h3>
			<p>
			<input type="checkbox" id="system_all" name="system_all" value="yes"  checked="checked" onChange="javascript:pts_rmm_schedule_days_toggle(this);" /> <strong>All Systems</strong>';


			$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID FROM phoromatic_systems WHERE AccountID = :account_id AND State >= 0 ORDER BY Title ASC');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$result = $stmt->execute();

			if($row = $result->fetchArray())
			{
				$main .= '<h4>Systems: ';
				do
				{
					$main .= '<input type="checkbox" name="run_on_systems[]" value="' . $row['SystemID'] . '" /> ' . $row['Title'] . ' ';
				}
				while($row = $result->fetchArray());
				$main .= '</h4>';
			}

			$stmt = phoromatic_server::$db->prepare('SELECT GroupName FROM phoromatic_groups WHERE AccountID = :account_id ORDER BY GroupName ASC');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$result = $stmt->execute();

			if($row = $result->fetchArray())
			{
				$main .= '<h4>Groups: ';
				do
				{
					$main .= '<input type="checkbox" name="run_on_groups[]" value="' . $row['GroupName'] . '" /> ' . $row['GroupName'] . ' ';
				}
				while($row = $result->fetchArray());
				$main .= '</h4>';
			}

			$main .= '</p>
			<h3>Description:</h3>
			<p><textarea name="schedule_description" id="schedule_description" cols="50" rows="3"></textarea></p>

			<table class="pts_phoromatic_schedule_type">
<tr>
  <td><h3>Time-Based Testing</h3><em>Time-based testing allows tests to automatically commence at a given time on a defined cycle each day/week. This option is primarly aimed for those wishing to run a set of benchmarks every morning or night or at another defined period.</em></td>
  <td><h3>Run Time:</h3>
			<p><select name="schedule_hour" id="schedule_hour">';
			for($i = 0; $i <= 23; $i++)
			{
				$i_f = (strlen($i) == 1 ? '0' . $i : $i);
				$main .= '<option value="' . $i_f . '">' . $i_f . '</option>';
			}

			$main .= '</select> <select name="schedule_minute" id="schedule_minute">';

			for($i = 0; $i < 60; $i += 10)
			{
				$i_f = (strlen($i) == 1 ? '0' . $i : $i);
				$main .= '<option value="' . $i_f . '">' . $i_f . '</option>';
			}

			$main .= '</select><h3>Active On:</h3><p>';

			$week = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
			foreach($week as $index => $day)
			{
				$main .= '<input type="checkbox" name="days_active[]" value="' . $index . '" /> ' . $day;
			}

			$main .= '</p></td>
</tr>
<tr>
  <td><h3>Trigger-Based Testing</h3><em>To carry out trigger-based testing, you can simply have an external process/script trigger (&quot;ping&quot;) a specialized URL whenever an event occurs to commence a new round of testing. This is the most customizable approach to having Phoromatic run tests on a system if you wish to have it occur whenever a Git/SVN commit takes place or other operations.</em></td>
  <td><h3>TODO IMPLEMENT UI</h3></td>
</tr>
<tr>
  <td><h3>One-Time Test</h3><em>If you wish to just run a single set of tests once on a given set of systems via Phoromatic, without any further scheduling, this is the option.</em></td>
  <td><h3>TODO IMPLEMENT UI</h3></td>
</tr>
</table>

			<h3><em>Indicates optional field.</em></h3>
			<p align="right"><input name="submit" value="Add Schedule" type="submit" onclick="return pts_rmm_validate_schedule();" /></p>
			</form>';
			echo phoromatic_webui_main($main, phoromatic_webui_right_panel_logged_in());
			echo phoromatic_webui_footer();
	}
}

?>
