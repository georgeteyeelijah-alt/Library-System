
CREATE DATABASE IF NOT EXISTS library_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE library_db;


CREATE TABLE Branches (
    branch_id   INT PRIMARY KEY AUTO_INCREMENT,
    name        VARCHAR(100) NOT NULL,
    location    VARCHAR(100)
);

CREATE TABLE Authors (
    author_id   INT PRIMARY KEY AUTO_INCREMENT,
    first_name  VARCHAR(50) NOT NULL,
    last_name   VARCHAR(50) NOT NULL
);

CREATE TABLE Subjetcs (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    name        VARCHAR(50) NOT NULL UNIQUE
);


CREATE TABLE Books (
    book_id       INT PRIMARY KEY AUTO_INCREMENT,
    title         VARCHAR(200) NOT NULL,
    author_id     INT,
    category_id   INT,
    branch_id     INT NOT NULL,
    total_copies  INT NOT NULL DEFAULT 1 CHECK (total_copies >= 0),
    FOREIGN KEY (author_id)   REFERENCES Authors(author_id)     ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES Categories(category_id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id)   REFERENCES Branches(branch_id)     ON DELETE RESTRICT
);


CREATE TABLE Members (
    member_id    INT PRIMARY KEY AUTO_INCREMENT,
    first_name   VARCHAR(50) NOT NULL,
    last_name    VARCHAR(50) NOT NULL,
    email        VARCHAR(100) UNIQUE,
    member_type  VARCHAR(20) DEFAULT 'student',  
    branch_id    INT NOT NULL,
    FOREIGN KEY (branch_id) REFERENCES Branches(branch_id) ON DELETE RESTRICT
);



CREATE TABLE Loans (
    loan_id             INT PRIMARY KEY AUTO_INCREMENT,
    book_id             INT NOT NULL,
    member_id           INT NOT NULL,
    loan_date           DATE NOT NULL,
    due_date             DATE NOT NULL,
    return_date         DATE NULL,                         -- NULL = still checked out
    sync_status         VARCHAR(20) NOT NULL DEFAULT 'local_only',
    created_locally_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_synced_at      DATETIME NULL,
    FOREIGN KEY (book_id)   REFERENCES Books(book_id)     ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES Members(member_id) ON DELETE CASCADE
);


CREATE INDEX idx_books_title    ON Books(title);
CREATE INDEX idx_loans_return   ON Loans(return_date);
CREATE INDEX idx_loans_due      ON Loans(due_date);
CREATE INDEX idx_members_email  ON Members(email);

INSERT INTO Branches (name, location) VALUES
    ('Lancaster University Ghana Library', 'Accra, Ghana');

INSERT INTO Authors (first_name, last_name) VALUES
    ('Chinua', 'Achebe'),
    ('Kenneth', 'Laudon'),
    ('Jane', 'Laudon');

INSERT INTO Categories (name) VALUES
    ('Fiction'),
    ('Information Systems'),
    ('Business');

INSERT INTO Books (title, author_id, category_id, branch_id, total_copies) VALUES
    ('Things Fall Apart', 1, 1, 1, 3),
    ('Management Information Systems', 2, 2, 1, 5);

INSERT INTO Members (first_name, last_name, email, member_type, branch_id) VALUES
    ('Ama', 'Owusu', 'ama.owusu@lancaster.edu.gh', 'student', 1),
    ('Kwame', 'Boateng', 'kwame.boateng@lancaster.edu.gh', 'staff', 1);

INSERT INTO Loans (book_id, member_id, loan_date, due_date, return_date, sync_status) VALUES
    (1, 1, '2026-07-20', '2026-08-03', NULL, 'local_only'),
    (2, 2, '2026-07-15', '2026-07-29', '2026-07-27', 'synced');

