CREATE OR REPLACE VIEW v_etablissement_buffer AS
SELECT
    es.id AS id_etablissement,
    es.nom,
    es.id_type,
    es.id_arrondissement,
    ST_Buffer(es.geom::geography, 500)::geometry AS buffer_geom
FROM etablissement_sante es
WHERE es.id_type = 5; 


CREATE OR REPLACE VIEW v_buffer_union_par_arrondissement AS
SELECT
    a.id AS id_arrondissement,
    ST_Union(eb.buffer_geom) AS buffer_union
FROM arrondissement a
LEFT JOIN v_etablissement_buffer eb ON eb.id_arrondissement = a.id
GROUP BY a.id;

CREATE OR REPLACE VIEW v_couverture_arrondissement AS
SELECT
    a.id,
    a.code,
    a.nom,
    a.superficie_km2,
    ST_Area(a.geom) AS superficie_totale,
    COALESCE(ST_Area(ST_Intersection(a.geom, bu.buffer_union)), 0) AS superficie_couverte,
    COALESCE(ST_Area(a.geom) - ST_Area(ST_Intersection(a.geom, bu.buffer_union)), ST_Area(a.geom)) AS superficie_non_couverte,
    CASE
        WHEN ST_Area(a.geom) > 0 THEN
            (COALESCE(ST_Area(ST_Intersection(a.geom, bu.buffer_union)), 0) / ST_Area(a.geom)) * 100
        ELSE 0
    END AS pourcentage_couvert,
    (SELECT COUNT(*) FROM etablissement_sante es WHERE es.id_arrondissement = a.id) AS nb_etablissements
FROM arrondissement a
LEFT JOIN v_buffer_union_par_arrondissement bu ON bu.id_arrondissement = a.id;

