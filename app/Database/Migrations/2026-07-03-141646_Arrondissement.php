<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Arrondissement extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'code' => [
                'type'          => 'VARCHAR',
                'constraint'    =>  12,
                'null'          => false,
            ],
            'nom' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ],
            'superficie_km2' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
            ],
            'geom' => [
                'type' => 'GEOMETRY',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('Arrondissement');

        // Optimisation PostGIS : Enforce MultiPolygon ou Polygon pour les contours et Index Spatial
        $this->db->query("ALTER TABLE Arrondissement ALTER COLUMN geom TYPE geometry(MultiPolygon, 4326) USING ST_SetSRID(geom, 4326);");
        $this->db->query("CREATE INDEX idx_arrondissement_geom ON Arrondissement USING GIST (geom);");
    }

    public function down()
    {
        $this->forge->dropTable('Arrondissement');
    }
}