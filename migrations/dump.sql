CREATE TABLE IF NOT EXISTS users
(
    id
    SERIAL
    PRIMARY
    KEY,
    username
    VARCHAR
(
    255
) NOT NULL UNIQUE,
    password VARCHAR
(
    255
) NOT NULL,
    balance DECIMAL
(
    10,
    2
) NOT NULL DEFAULT 0.00
    );

CREATE TABLE IF NOT EXISTS transactions
(
    id
    SERIAL
    PRIMARY
    KEY,
    sender_id
    INTEGER
    NOT
    NULL,
    recipient_id
    INTEGER
    NOT
    NULL,
    amount
    DECIMAL
(
    10,
    2
) NOT NULL,
    created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    FOREIGN KEY
(
    sender_id
) REFERENCES users
(
    id
),
    FOREIGN KEY
(
    recipient_id
) REFERENCES users
(
    id
)
    );

INSERT INTO users (username, password, balance)
VALUES ('user1', 'password1', 1000.00),
       ('user2', 'password2', 500.00);