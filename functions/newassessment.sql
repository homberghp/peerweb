with ad as (
select contestant,
       criterium,grade,
       avg(grade) over (partition by contestant,criterium) as critgrade,
       (avg(grade) over(partition by contestant,criterium))/(avg(grade) over(partition by criterium)) as multiplier
       from assessment where prjtg_id=7959 order by contestant,criterium),
od as (
select distinct contestant, criterium,critgrade,multiplier from ad order by contestant,criterium)
select *  from crosstab2($$select * from ad$$);
