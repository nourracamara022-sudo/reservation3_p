<?php
require_once "Connexion.php";

$par_page = 10;
$page = isset($_GET["page"]) ? (int) $_GET["page"] : 1;

if ($page < 1) {
    $page = 1;
}

$afficher_formulaire = false;
$mode_modification = false;
$id_modification = 0;
$erreur = "";

$nom_agent = "";
$objet = "";
$date_reunion = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";
    if ($action === "ajouter") {

        $afficher_formulaire = true;
    } elseif ($action === "charger_modification") {

        $id = (int) ($_POST["id"] ?? 0);

        if ($id > 0) {
            $requete = $pdo->prepare(
                "SELECT id, nom_agent, objet, date_reunion
                 FROM demandes
                 WHERE id = ?"
            );

            $requete->execute([$id]);
            $demande = $requete->fetch(PDO::FETCH_ASSOC);

            if ($demande) {
                $afficher_formulaire = true;
                $mode_modification = true;
                $id_modification = (int) $demande["id"];
                $nom_agent = $demande["nom_agent"];
                $objet = $demande["objet"];
                $date_reunion = $demande["date_reunion"];
            }
        }
    } elseif ($action === "enregistrer") {

        $afficher_formulaire = true;

        $nom_agent = trim($_POST["nom_agent"] ?? "");
        $objet = trim($_POST["objet"] ?? "");
        $date_reunion = $_POST["date_reunion"] ?? "";

        if (
            $nom_agent !== "" &&
            $objet !== "" &&
            $date_reunion !== ""
        ) {
            $requete = $pdo->prepare(
                "INSERT INTO demandes
                 (nom_agent, objet, date_reunion)
                 VALUES (?, ?, ?)"
            );

            $requete->execute([
                $nom_agent,
                $objet,
                $date_reunion
            ]);
            $requete_total = $pdo->query(
                "SELECT COUNT(*) FROM demandes"
            );

            $total_apres_ajout = (int) $requete_total->fetchColumn();

            $derniere_page = (int) ceil(
                $total_apres_ajout / $par_page
            );

            header(
                "Location: index2.php?page=" . max(1, $derniere_page)
            );
            exit;

        } else {
            $erreur = "Veuillez remplir tous les champs.";
        }
    } elseif ($action === "mettre_a_jour") {

        $afficher_formulaire = true;
        $mode_modification = true;

        $id_modification = (int) ($_POST["id"] ?? 0);
        $nom_agent = trim($_POST["nom_agent"] ?? "");
        $objet = trim($_POST["objet"] ?? "");
        $date_reunion = $_POST["date_reunion"] ?? "";

        if (
            $id_modification > 0 &&
            $nom_agent !== "" &&
            $objet !== "" &&
            $date_reunion !== ""
        ) {
            $requete = $pdo->prepare(
                "UPDATE demandes
                 SET nom_agent = ?, objet = ?, date_reunion = ?
                 WHERE id = ?"
            );

            $requete->execute([
                $nom_agent,
                $objet,
                $date_reunion,
                $id_modification
            ]);

            header("Location: index2.php?page=" . $page);
            exit;

        } else {
            $erreur = "Veuillez remplir tous les champs.";
        }
    } elseif ($action === "supprimer") {

        $id = (int) ($_POST["id"] ?? 0);

        if ($id > 0) {
            $requete = $pdo->prepare(
                "DELETE FROM demandes WHERE id = ?"
            );

            $requete->execute([$id]);
        }

        header("Location: index2.php?page=" . $page);
        exit;
    }
}

$requete_total = $pdo->query(
    "SELECT COUNT(*) FROM demandes"
);

$total_demandes = (int) $requete_total->fetchColumn();
$total_pages = (int) ceil($total_demandes / $par_page);
if ($total_pages > 0 && $page > $total_pages) {
    $page = $total_pages;
}
$debut = ($page - 1) * $par_page;
$requete = $pdo->prepare(
    "SELECT id, nom_agent, objet, date_reunion
     FROM demandes
     ORDER BY id ASC
     LIMIT :limite OFFSET :debut"
);

$requete->bindValue(
    ":limite",
    $par_page,
    PDO::PARAM_INT
);

$requete->bindValue(
    ":debut",
    $debut,
    PDO::PARAM_INT
);

$requete->execute();

$demandes = $requete->fetchAll(PDO::FETCH_ASSOC);
$premiere_demande = $total_demandes > 0 ? $debut + 1 : 0;

$derniere_demande = min(
    $debut + $par_page,
    $total_demandes
);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Gestion des demandes de réservation</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="conteneur">

    <h1>Gestion des demandes de réservation</h1>

    <!-- Bouton Ajouter -->
    <form method="POST"
          action="index2.php?page=<?= $page ?>"
          class="zone-ajouter">

        <input type="hidden"
               name="action"
               value="ajouter">

        <button type="submit">Ajouter</button>
    </form>

    <!-- Fenêtre pop-up -->
    <?php if ($afficher_formulaire) : ?>

        <div class="popup-overlay">

            <section class="popup-contenu">

                <a href="index2.php?page=<?= $page ?>"
                   class="fermer-popup"
                   title="Fermer">&times;</a>

                <h2>
                    <?= $mode_modification
                        ? "Modifier une demande"
                        : "Nouvelle demande" ?>
                </h2>

                <?php if ($erreur !== "") : ?>

                    <p class="erreur">
                        <?= htmlspecialchars($erreur) ?>
                    </p>

                <?php endif; ?>

                <form method="POST"
                      action="index2.php?page=<?= $page ?>">

                    <?php if ($mode_modification) : ?>

                        <input type="hidden"
                               name="action"
                               value="mettre_a_jour">

                        <input type="hidden"
                               name="id"
                               value="<?= $id_modification ?>">

                    <?php else : ?>

                        <input type="hidden"
                               name="action"
                               value="enregistrer">

                    <?php endif; ?>

                    <label for="nom_agent">
                        Nom de l'agent
                    </label>

                    <input type="text"
                           id="nom_agent"
                           name="nom_agent"
                           value="<?= htmlspecialchars($nom_agent) ?>"
                           required>

                    <label for="objet">
                        Objet de la réunion
                    </label>

                    <input type="text"
                           id="objet"
                           name="objet"
                           value="<?= htmlspecialchars($objet) ?>"
                           required>

                    <label for="date_reunion">
                        Date de la réunion
                    </label>

                    <input type="date"
                           id="date_reunion"
                           name="date_reunion"
                           value="<?= htmlspecialchars($date_reunion) ?>"
                           required>

                    <button type="submit">
                        <?= $mode_modification
                            ? "Enregistrer les modifications"
                            : "Enregistrer" ?>
                    </button>

                </form>

            </section>
        </div>

    <?php endif; ?>

    <!-- Tableau des demandes -->
    <section class="carte">

        <h2>Liste des demandes</h2>

        <p>
            Affichage de <?= $premiere_demande ?>
            à <?= $derniere_demande ?>
            sur <?= $total_demandes ?> demande(s).
        </p>

        <div class="tableau-conteneur">

            <table>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom de l'agent</th>
                        <th>Objet</th>
                        <th>Date de la réunion</th>
                        <th>Modifier</th>
                        <th>Supprimer</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($demandes as $demande) : ?>

                        <tr>

                            <td>
                                <?= (int) $demande["id"] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($demande["nom_agent"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($demande["objet"]) ?>
                            </td>

                            <td>
                                <?= date(
                                    "d/m/Y",
                                    strtotime($demande["date_reunion"])
                                ) ?>
                            </td>

                            <td>
                                <form method="POST"
                                      action="index2.php?page=<?= $page ?>">

                                    <input type="hidden"
                                           name="action"
                                           value="charger_modification">

                                    <input type="hidden"
                                           name="id"
                                           value="<?= (int) $demande["id"] ?>">

                                    <button type="submit">
                                        Modifier
                                    </button>

                                </form>
                            </td>

                            <td>
                                <form method="POST"
                                      action="index2.php?page=<?= $page ?>">

                                    <input type="hidden"
                                           name="action"
                                           value="supprimer">

                                    <input type="hidden"
                                           name="id"
                                           value="<?= (int) $demande["id"] ?>">

                                    <button type="submit"
                                            class="bouton-supprimer">
                                        Supprimer
                                    </button>

                                </form>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    <?php if (count($demandes) === 0) : ?>

                        <tr>
                            <td colspan="6">
                                Aucune demande enregistrée.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>
        <?php if ($total_pages > 1) : ?>

            <div class="pagination">

                <?php if ($page > 1) : ?>

                    <a href="index2.php?page=<?= $page - 1 ?>">
                        Précédent
                    </a>

                <?php endif; ?>

                <?php for (
                    $numero = 1;
                    $numero <= $total_pages;
                    $numero++
                ) : ?>

                    <?php if ($numero === $page) : ?>

                        <strong class="page-active">
                            <?= $numero ?>
                        </strong>

                    <?php else : ?>

                        <a href="index2.php?page=<?= $numero ?>">
                            <?= $numero ?>
                        </a>

                    <?php endif; ?>

                <?php endfor; ?>

                <?php if ($page < $total_pages) : ?>

                    <a href="index2.php?page=<?= $page + 1 ?>">
                        Suivant
                    </a>

                <?php endif; ?>

            </div>

        <?php endif; ?>

    </section>

</div>

</body>
</html>