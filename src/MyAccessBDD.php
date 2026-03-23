<?php
include_once("AccessBDD.php");

/**
 * Classe de construction des requêtes SQL
 * hérite de AccessBDD qui contient les requêtes de base
 * Pour ajouter une requête :
 * - créer la fonction qui crée une requête (prendre modèle sur les fonctions 
 *   existantes qui ne commencent pas par 'traitement')
 * - ajouter un 'case' dans un des switch des fonctions redéfinies 
 * - appeler la nouvelle fonction dans ce 'case'
 */
class MyAccessBDD extends AccessBDD {
	    
    /**
     * constructeur qui appelle celui de la classe mère
     */
    public function __construct(){
        try{
            parent::__construct();
        }catch(\Exception $e){
            throw $e;
        }
    }

    /**
     * demande de recherche
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return array|null tuples du résultat de la requête ou null si erreur
     * @override
     */	
    protected function traitementSelect(string $table, ?array $champs) : ?array{
        switch($table){  
            case "livre" :
                return $this->selectAllLivres();
            case "dvd" :
                return $this->selectAllDvd();
            case "revue" :
                return $this->selectAllRevues();
            case "exemplaire" :
                return $this->selectExemplairesRevue($champs);
            case "genre" :
            case "public" :
            case "rayon" :
            case "etat" :
                // select portant sur une table contenant juste id et libelle
                return $this->selectTableSimple($table);
            case "commandes_doc" : 
                return $this->getListeCommandes($champs);
            case "niveaux_suivi" :
                return $this->getNiveauxSuivi();
            case "abonnements_revue":
                return $this->getAbonnementsRevue($champs);
            case "abonnements_expirants":
                return $this->getAbonnementsExpirants();
            case "authentification":
                 return $this->getAuthentification($champs);
            case "" :
                // return $this->uneFonction(parametres);
            default:
                // cas général
                return $this->selectTuplesOneTable($table, $champs);
        }	
    }

    /**
     * demande d'ajout (insert)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples ajoutés ou null si erreur
     * @override
     */	
    protected function traitementInsert(string $table, ?array $champs) : ?int{
        switch($table){
            case "gestion_cmd":
                $id = $this->creerIdAuto();
                $res1 = $this->insertOneTupleOneTable("commande",[
                    "id" => $id, 
                    "dateCommande" => $champs["dateCommande"], 
                    "montant" => $champs["montant"]
                ]);
                
                if ($res1 !== null) {
                    $res2 = $this->insertOneTupleOneTable("commandedocument",[
                        "id" => $id, 
                        "nbExemplaire" => $champs["nbExemplaire"], 
                        "idLivreDvd" => $champs["idLivreDvd"]
                    ]);
                    
                    if ($res2 !== null) {
                        return 1;
                    } else {
                        $this->deleteTuplesOneTable("commande", ["id" => $id]);
                        return null;
                    }
                }
                return null;
            
            case "gestion_abonnement":
                $id = $this->creerIdAuto();
                $res1 = $this->insertOneTupleOneTable("commande",[
                    "id" => $id, 
                    "dateCommande" => $champs["dateCommande"], 
                    "montant" => $champs["montant"]
                ]);

                if ($res1 !== null) {
                    $res2 = $this->insertOneTupleOneTable("abonnement",[
                        "id" => $id, 
                        "dateFinAbonnement" => $champs["dateFinAbonnement"], 
                        "idRevue" => $champs["idRevue"]
                    ]);

                    if ($res2 !== null) {
                        return 1;
                    } else {
                        $this->deleteTuplesOneTable("commande",["id" => $id]);
                        return null;
                    }
                }
                return null;
                
            default:                    
                return $this->insertOneTupleOneTable($table, $champs);	
        }
    }
    
    /**
     * demande de modification (update)
     * @param string $table
     * @param string|null $id
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples modifiés ou null si erreur
     * @override
     */	
    protected function traitementUpdate(string $table, ?string $id, ?array $champs) : ?int{
        switch($table){
            case "" :
                // return $this->uneFonction(parametres);
            default:                    
                // cas général
                return $this->updateOneTupleOneTable($table, $id, $champs);
        }	
    }  
    
    /**
     * demande de suppression (delete)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples supprimés ou null si erreur
     * @override
     */	
    protected function traitementDelete(string $table, ?array $champs) : ?int{
        switch($table){
            case "" :
                // return $this->uneFonction(parametres);
            case "gestion_abonnement":
                $res1 = $this->deleteTuplesOneTable("abonnement",["id" => $champs["id"]]);
                if ($res1 !== null) {
                    $this->deleteTuplesOneTable("commande", ["id" => $champs["id"]]);
                    return 1;
                }
                return null;
            default:                    
                // cas général
                return $this->deleteTuplesOneTable($table, $champs);	
        }
    }	    
        
    /**
     * récupère les tuples d'une seule table
     * @param string $table
     * @param array|null $champs
     * @return array|null 
     */
    private function selectTuplesOneTable(string $table, ?array $champs) : ?array{
        if(empty($champs)){
            // tous les tuples d'une table
            $requete = "select * from $table;";
            return $this->conn->queryBDD($requete);  
        }else{
            // tuples spécifiques d'une table
            $requete = "select * from $table where ";
            foreach ($champs as $key => $value){
                $requete .= "$key=:$key and ";
            }
            // (enlève le dernier and)
            $requete = substr($requete, 0, strlen($requete)-5);	          
            return $this->conn->queryBDD($requete, $champs);
        }
    }	

    /**
     * demande d'ajout (insert) d'un tuple dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples ajoutés (0 ou 1) ou null si erreur
     */	
    private function insertOneTupleOneTable(string $table, ?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        // construction de la requête
        $requete = "insert into $table (";
        foreach ($champs as $key => $value){
            $requete .= "$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);
        $requete .= ") values (";
        foreach ($champs as $key => $value){
            $requete .= ":$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);
        $requete .= ");";
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * demande de modification (update) d'un tuple dans une table
     * @param string $table
     * @param string\null $id
     * @param array|null $champs 
     * @return int|null nombre de tuples modifiés (0 ou 1) ou null si erreur
     */	
    private function updateOneTupleOneTable(string $table, ?string $id, ?array $champs) : ?int {
        if(empty($champs)){
            return null;
        }
        if(is_null($id)){
            return null;
        }
        // construction de la requête
        $requete = "update $table set ";
        foreach ($champs as $key => $value){
            $requete .= "$key=:$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);				
        $champs["id"] = $id;
        $requete .= " where id=:id;";		
        return $this->conn->updateBDD($requete, $champs);	        
    }
    
    /**
     * demande de suppression (delete) d'un ou plusieurs tuples dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples supprimés ou null si erreur
     */
    private function deleteTuplesOneTable(string $table, ?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        // construction de la requête
        $requete = "delete from $table where ";
        foreach ($champs as $key => $value){
            $requete .= "$key=:$key and ";
        }
        // (enlève le dernier and)
        $requete = substr($requete, 0, strlen($requete)-5);   
        return $this->conn->updateBDD($requete, $champs);	        
    }
 
    /**
     * récupère toutes les lignes d'une table simple (qui contient juste id et libelle)
     * @param string $table
     * @return array|null
     */
    private function selectTableSimple(string $table) : ?array{
        $requete = "select * from $table order by libelle;";		
        return $this->conn->queryBDD($requete);	    
    }
    
    /**
     * récupère toutes les lignes de la table Livre et les tables associées
     * @return array|null
     */
    private function selectAllLivres() : ?array{
        $requete = "Select l.id, l.ISBN, l.auteur, d.titre, d.image, l.collection, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from livre l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";		
        return $this->conn->queryBDD($requete);
    }	

    /**
     * récupère toutes les lignes de la table DVD et les tables associées
     * @return array|null
     */
    private function selectAllDvd() : ?array{
        $requete = "Select l.id, l.duree, l.realisateur, d.titre, d.image, l.synopsis, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from dvd l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";	
        return $this->conn->queryBDD($requete);
    }	

    /**
     * récupère toutes les lignes de la table Revue et les tables associées
     * @return array|null
     */
    private function selectAllRevues() : ?array{
        $requete = "Select l.id, l.periodicite, d.titre, d.image, l.delaiMiseADispo, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from revue l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }	

    /**
     * récupère tous les exemplaires d'une revue
     * @param array|null $champs 
     * @return array|null
     */
    private function selectExemplairesRevue(?array $champs) : ?array{
        if(empty($champs)){
            return null;
        }
        if(!array_key_exists('id', $champs)){
            return null;
        }
        $champNecessaire['id'] = $champs['id'];
        $requete = "Select e.id, e.numero, e.dateAchat, e.photo, e.idEtat ";
        $requete .= "from exemplaire e join document d on e.id=d.id ";
        $requete .= "where e.id = :id ";
        $requete .= "order by e.dateAchat DESC";
        return $this->conn->queryBDD($requete, $champNecessaire);
    }
    
    /**
     * 
     * @param array|null $champs
     * @return array|null
     */
    private function getListeCommandes(?array $champs) : ?array {
        if (empty($champs) || !array_key_exists('id', $champs)) return null;
        $sql = "SELECT cd.id, c.dateCommande, c.montant, cd.nbExemplaire, cd.idLivreDvd, cd.idSuivi, s.intitule as libelle ";
        $sql .= "FROM commandedocument cd JOIN commande c ON c.id = cd.id JOIN etape_suivi s ON s.id = cd.idSuivi ";
        $sql .= "WHERE cd.idLivreDvd = :id ORDER BY c.dateCommande DESC";
        return $this->conn->queryBDD($sql, ['id' => $champs['id']]);
    }
    
    /**
     * 
     * @return string
     */
    private function creerIdAuto() : string {
        $sql = "SELECT MAX(id) AS maxId FROM commande;";
        $res = $this->conn->queryBDD($sql);
        
        if ($res !== null && !empty($res[0]["maxId"])) {
            $maxId = (int)$res[0]["maxId"];
            return str_pad((string)($maxId + 1), 5, "0", STR_PAD_LEFT);
        }
        return "00001";
    }
    
    /**
    * Récupère les étapes de suivi triées par leur rang logique
    * @return array|null
    */
    private function getNiveauxSuivi() : ?array {
        $sql = "SELECT * FROM etape_suivi ORDER BY rang ASC;";
        return $this->conn->queryBDD($sql);
    }
    
    /**
     * Récupère l'historique des abonnements d'une revue
     * @param array|null $champs
     * @return array|null
     */
    private function getAbonnementsRevue(?array $champs) : ?array {
        if (empty($champs) || !array_key_exists('id', $champs)) return null;
        $sql = "SELECT a.id, c.dateCommande, c.montant, a.dateFinAbonnement, a.idRevue ";
        $sql .= "FROM abonnement a JOIN commande c ON c.id = a.id ";
        $sql .= "WHERE a.idRevue = :id ORDER BY c.dateCommande DESC";
        return $this->conn->queryBDD($sql,['id' => $champs['id']]);
    }

    /**
     * Récupère les abonnements expirant dans moins de 30 jours
     * @return array|null
     */
    private function getAbonnementsExpirants() : ?array {
        $sql = "SELECT a.id, c.dateCommande, c.montant, a.dateFinAbonnement, a.idRevue, d.titre as Titre ";
        $sql .= "FROM abonnement a JOIN commande c ON c.id = a.id ";
        $sql .= "JOIN document d ON a.idRevue = d.id ";
        $sql .= "WHERE DATEDIFF(a.dateFinAbonnement, CURDATE()) BETWEEN 0 AND 30 ";
        $sql .= "ORDER BY a.dateFinAbonnement ASC";
        return $this->conn->queryBDD($sql);
    }
    
    /**
     * Vérifie les identifiants d'un utilisateur
     * Retourne les informations de l'utilisateur et son service si ok, sinon null
     * @param array|null $champs
     * @return array|null
     */
    private function getAuthentification(?array $champs) : ?array {
        if (empty($champs) || !array_key_exists('login', $champs) || !array_key_exists('password', $champs)) {
            return null;
        }
        // Récupération de  l'utilisateur et du libellé de son service grâce à une jointure
        $sql = "SELECT u.id, u.login, u.idService, s.libelle as service ";
        $sql .= "FROM utilisateur u JOIN service s ON u.idService = s.id ";
        $sql .= "WHERE u.login = :login AND u.password = :password";
        
        return $this->conn->queryBDD($sql, ['login' => $champs['login'], 'password' => $champs['password']]);
    }
    
    
    
}
