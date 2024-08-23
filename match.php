<?php
/**
 * Created by PhpStorm.
 * User: erik
 * Date: 04.09.17
 * Time: 20:02
 */

include 'vendor/autoload.php';


function create_season($name, $bet_type, $settings, $start_time=NULL) {
    require("config.php");

    $statement = $pdo->prepare("INSERT INTO ".$db_name.".season (name, bet_type, settings, start_time) VALUES (:name, :bet_type, :settings, FROM_UNIXTIME(:start_time))");
    $statement->bindValue(':name', $name, PDO::PARAM_STR);
    $statement->bindValue(':bet_type', $bet_type, PDO::PARAM_STR);
    $statement->bindValue(':settings', json_encode($settings), PDO::PARAM_STR);
    $statement->bindValue(':start_time', $start_time, PDO::PARAM_INT);
    $result = $statement->execute();

    return $result;
}

function update_season_start_time($season_id) {
    require("config.php");

    $statement = $pdo->prepare(
        "SELECT start_time FROM ".$db_name.".matchday 
         WHERE (season_id = :season_id AND start_time IS NOT NULL)
         ORDER BY start_time ASC
         LIMIT 1");
    $statement->bindValue(':season_id', $season_id, PDO::PARAM_INT);
    $statement->execute();
    $start_time = $statement->fetch(PDO::FETCH_ASSOC)['start_time'];

    if ($start_time !== NULL) {
        $start_time = strtotime($start_time);
    }

    $statement = $pdo->prepare("UPDATE ".$db_name.".season SET start_time=FROM_UNIXTIME(:start_time) WHERE id=:id");
    $statement->bindValue(':id', $season_id, PDO::PARAM_INT);
    $statement->bindValue(':start_time', $start_time, PDO::PARAM_INT);
    $statement->execute();
}

function get_season_ids($userid=NULL) {
    require("config.php");

    if ($userid !== NULL) {
        $statement = $pdo->prepare("SELECT id FROM ".$db_name.".season INNER JOIN betgroup_season ON season.id=betgroup_season.season_id INNER JOIN betgroup_user ON betgroup_season.betgroup_id=betgroup_user.betgroup_id WHERE user_id=:user_id");
        $statement->bindValue(':user_id', $userid, PDO::PARAM_INT);

    } else {
        $statement = $pdo->prepare("SELECT id FROM ".$db_name.".season ORDER BY start_time ASC, name ASC");
    }

    $statement->execute();

    $id_list = [];
    foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $season) {
        $id_list[] = $season['id'];
    }

    return $id_list;
}

function get_seasons($ids) {
    require("config.php");

    $seasons = [];

    foreach ($ids as $id) {
        $statement = $pdo->prepare("SELECT * FROM ".$db_name.".season WHERE id = :id");
        $statement->execute(array('id' => $id));
        $seasons[$id] = $statement->fetch(PDO::FETCH_ASSOC);
    }

    return $seasons;
}

function get_seasonname($id) {
    require("config.php");

    $statement = $pdo->prepare("SELECT name FROM ".$db_name.".season WHERE id =".$id);
    $statement->execute();
    $seasonname = $statement->fetch(PDO::FETCH_ASSOC);

    return $seasonname;
}

function all_seasons() {
    require ("config.php");

    $statement = $pdo->prepare("SELECT * FROM " . $db_name . ".season ");
    $statement->execute();
    $seasons = $statement->fetchAll(PDO::FETCH_ASSOC);

    return $seasons;
}

function get_season_bettype($id) {
    require("config.php");

    $statement = $pdo->prepare("SELECT bet_type FROM ".$db_name.".season WHERE id='".$id."'");
    $statement->execute();
    $bettype = $statement->fetch(PDO::FETCH_ASSOC)['bet_type'];


    return $bettype;
}

function create_matchday($season_id, $name, $start_time=NULL) {
    require("config.php");

    $statement = $pdo->prepare("INSERT INTO ".$db_name.".matchday (season_id, name, start_time) VALUES (:season_id, :name, FROM_UNIXTIME(:start_time))");
    $statement->bindValue(':season_id', $season_id, PDO::PARAM_INT);
    $statement->bindValue(':name', $name, PDO::PARAM_STR);
    $statement->bindValue(':start_time', $start_time, PDO::PARAM_INT);
    $result = $statement->execute();

    return $result;
}

function update_matchday_start_time($matchday_id) {
    require("config.php");

    $statement = $pdo->prepare(
        "SELECT start_time FROM ".$db_name.".match 
         WHERE (matchday_id = :matchday_id AND start_time IS NOT NULL)
         ORDER BY start_time ASC
         LIMIT 1");
    $statement->bindValue(':matchday_id', $matchday_id, PDO::PARAM_INT);
    $statement->execute();
    $start_time = $statement->fetch(PDO::FETCH_ASSOC)['start_time'];

    if ($start_time !== NULL) {
        $start_time = strtotime($start_time);
    }

    $statement = $pdo->prepare("UPDATE ".$db_name.".matchday SET start_time=FROM_UNIXTIME(:start_time) WHERE id=:id");
    $statement->bindValue(':id', $matchday_id, PDO::PARAM_INT);
    $statement->bindValue(':start_time', $start_time, PDO::PARAM_INT);
    $statement->execute();
}

function get_matchday_ids($season_id) {
    require("config.php");

    $statement = $pdo->prepare("SELECT id FROM ".$db_name.".matchday WHERE season_id = :season_id
        ORDER BY start_time ASC, name ASC");
    $statement->bindValue(':season_id', $season_id, PDO::PARAM_INT);
    $statement->execute();

    $id_list = [];
    foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $matchday) {
        $id_list[] = $matchday['id'];
    }

    return $id_list;
}

function get_matchdays($ids) {
    require("config.php");

    $matchdays = [];

    foreach ($ids as $id) {
        $statement = $pdo->prepare("SELECT * FROM ".$db_name.".matchday WHERE id = :id");
        $statement->execute(array('id' => $id));
        $matchdays[$id] = $statement->fetch(PDO::FETCH_ASSOC);
    }

    return $matchdays;
}

function create_match($matchday_id, $match_id_from_api) {
    require("config.php");

    // API URL erstellen, um das Match basierend auf der match_id von OpenLigaDB abzurufen
    $api_url = "https://api.openligadb.de/getmatchdata/" . $match_id_from_api;

    // Abrufen der Daten von der API
    $json_data = file_get_contents($api_url);

    // Überprüfen, ob die Daten erfolgreich abgerufen wurden
    if ($json_data === FALSE) {
        return false; // Fehler beim Abrufen der Daten
    }

    // Dekodieren der JSON-Daten in ein PHP-Array
    $match_info = json_decode($json_data, true);

    // Überprüfen, ob das Decoding erfolgreich war
    if ($match_info === NULL) {
        return false; // Fehler beim Dekodieren der Daten
    }

    $home_team = $match_info['Team1']['TeamName'];
    $home_logo = $match_info['Team1']['TeamIconUrl'];
    $guest_team = $match_info['Team2']['TeamName'];
    $guest_logo = $match_info['Team2']['TeamIconUrl'];
    $start_time = strtotime($match_info['MatchDateTime']);

    // check if matchday_id exists
    $statement = $pdo->prepare("SELECT * FROM ".$db_name.".matchday WHERE id = :id");
    $statement->execute(array('id' => $matchday_id));
    $matchday = $statement->fetch(PDO::FETCH_ASSOC);

    if ($matchday == false) {
        return $matchday;
    }

    // Schreiben der Daten in die Datenbank
    $statement = $pdo->prepare("INSERT INTO ".$db_name.".match (matchday_id, home_team, home_logo, guest_team, guest_logo, start_time) VALUES (:matchday_id, :home_team, :home_logo, :guest_team, :guest_logo, FROM_UNIXTIME(:start_time))");
    $result = $statement->execute(array(
        'matchday_id' => $matchday_id,
        'home_team' => $home_team,
        'home_logo' => $home_logo,
        'guest_team' => $guest_team,
        'guest_logo' => $guest_logo,
        'start_time' => $start_time
    ));

    return $result;
}

function delete_match($match_id) {
    require("config.php");

    $statement = $pdo->prepare("DELETE FROM ".$db_name.".match WHERE id=:id");
    $statement->bindValue(':id', $match_id, PDO::PARAM_INT);
    return $statement->execute();
}

function update_match($match_id) {
    require("config.php");
    require_once("bet.php");

    // get match information from the database
    $statement = $pdo->prepare("SELECT * FROM ".$db_name.".match WHERE id = :id");
    $statement->execute(array('id' => $match_id));
    $match = $statement->fetch(PDO::FETCH_ASSOC);

    if ($match == false) {
        return false;
    }

    // Get match_id_from_api from the database or another source (assuming it exists in the database)
    $match_id_from_api = $match['match_id_from_api'];

    // Check if the match has an associated match_id from the API
    if ($match_id_from_api !== NULL) {
        // Fetch match data from OpenLigaDB API
        $api_url = "https://api.openligadb.de/getmatchdata/" . $match_id_from_api;
        $json_data = file_get_contents($api_url);

        // Verify the API call was successful
        if ($json_data === FALSE) {
            return false;
        }

        // Decode the JSON data
        $match_info = json_decode($json_data, true);

        if ($match_info === NULL) {
            return false;
        }

        // Extract match details from API data
        $home_goals = $match_info['MatchResults'][0]['PointsTeam1'] ?? $match['home_goals']; // current home goals
        $guest_goals = $match_info['MatchResults'][0]['PointsTeam2'] ?? $match['guest_goals']; // current guest goals
        $start_time = strtotime($match_info['MatchDateTime']) ?? $match['start_time'];
        $finished = $match_info['MatchIsFinished'] ?? $match['finished'];
        $home_logo = $match_info['Team1']['TeamIconUrl'] ?? $match['home_logo'];
        $guest_logo = $match_info['Team2']['TeamIconUrl'] ?? $match['guest_logo'];
    }

    // Determine winner based on goals
    if ($finished) {
        if ($home_goals > $guest_goals) {
            $winner = 1;
        } elseif ($home_goals < $guest_goals) {
            $winner = 2;
        } else {
            $winner = 0; // draw
        }
    } else {
        $winner = NULL;
    }

    // Update match data in the database
    $statement = $pdo->prepare("UPDATE ".$db_name.".match 
        SET home_goals=:home_goals, guest_goals=:guest_goals, 
        finished=:finished, winner=:winner, start_time=FROM_UNIXTIME(:start_time), 
        home_logo=:home_logo, guest_logo=:guest_logo 
        WHERE id=:id");
        
    $statement->bindValue(':id', $match_id, PDO::PARAM_INT);
    $statement->bindValue(':home_goals', $home_goals, PDO::PARAM_INT);
    $statement->bindValue(':guest_goals', $guest_goals, PDO::PARAM_INT);
    $statement->bindValue(':finished', $finished, PDO::PARAM_BOOL);
    $statement->bindValue(':winner', $winner, PDO::PARAM_INT);
    $statement->bindValue(':start_time', $start_time, PDO::PARAM_INT);
    $statement->bindValue(':home_logo', $home_logo, PDO::PARAM_STR);
    $statement->bindValue(':guest_logo', $guest_logo, PDO::PARAM_STR);

    $result = $statement->execute();

    // Update points for users if the match is finished
    if ($finished) {
        foreach (all_users() as $user) {
            check_points($user['id'], $match_id);
        }
    }

    return $result;
}

function get_match_ids($matchday_id) {
    require("config.php");

    $statement = $pdo->prepare("SELECT id FROM ".$db_name.".match WHERE matchday_id = :matchday_id");
    $statement->bindValue(':matchday_id', $matchday_id, PDO::PARAM_INT);
    $statement->execute();

    $id_list = [];
    foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $match) {
        $id_list[] = $match['id'];
    }

    return $id_list;
}

function get_matches($ids) {
    require("config.php");

    $matches = [];

    foreach ($ids as $id) {
        $statement = $pdo->prepare("SELECT *, start_time - NOW() AS start FROM ".$db_name.".match WHERE id = :id");
        $statement->execute(array('id' => $id));
        $matches[$id] = $statement->fetch(PDO::FETCH_ASSOC);
    }

    return $matches;
}

?>
