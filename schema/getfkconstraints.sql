SELECT conname,
       conrelid::regclass AS fk_table
      ,CASE WHEN pg_get_constraintdef(c.oid) LIKE 'FOREIGN KEY %'
      	    THEN substring(pg_get_constraintdef(c.oid), 14, position(')' in pg_get_constraintdef(c.oid))-14)
	    END AS fk_column
      ,CASE WHEN pg_get_constraintdef(c.oid) LIKE 'FOREIGN KEY %'
            THEN substring(pg_get_constraintdef(c.oid), position(' REFERENCES ' in pg_get_constraintdef(c.oid))+12,
      	    	 position('(' in substring(pg_get_constraintdef(c.oid), 14))-position(' REFERENCES ' in pg_get_constraintdef(c.oid))+1)
      	END AS "PK_Table"
      ,CASE WHEN pg_get_constraintdef(c.oid) LIKE 'FOREIGN KEY %'
      	    THEN substring(pg_get_constraintdef(c.oid), position('(' in substring(pg_get_constraintdef(c.oid), 14))+14,
      	    position(')' in substring(pg_get_constraintdef(c.oid), position('(' in substring(pg_get_constraintdef(c.oid), 14))+14))-1)
       END AS "PK_Column"
       , case when c.confdeltype='c' then 'CASCADE'
              when c.confdeltype='r' then 'RESTRICT'
	      else ''
	 end as "ondelete"
       ,c.confupdtype,pg_get_constraintdef(c.oid)
FROM   pg_constraint c
JOIN   pg_namespace n ON n.oid = c.connamespace
WHERE  true
AND contype IN ('f', 'p ')
AND pg_get_constraintdef(c.oid) LIKE 'FOREIGN KEY %'
AND confdeltype  in ('r')
AND c.conrelid ='student'::regclass
ORDER  BY conname,pg_get_constraintdef(c.oid), conrelid::regclass::text, contype DESC;
