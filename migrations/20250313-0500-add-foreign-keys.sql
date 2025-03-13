alter table calendar_constructor
    add constraint calendar_constructor_users_id_fk
        foreign key (user_id) references users (id);

create unique index users_email_uindex
    on users (email);

create table u718471842_tpb.stripe
(
    id            bigint unsigned auto_increment
        primary key,
    customer_id   varchar(255) null,
    user_id       int          not null,
    customer_json json         null,
    constraint stripe_customer_id_uindex
        unique (customer_id),
    constraint stripe_user_id_uindex
        unique (user_id)
);

create index stripe_user_id_customer_id_index
    on u718471842_tpb.stripe (user_id, customer_id);




