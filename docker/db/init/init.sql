DROP TABLE IF EXISTS character_field_values CASCADE;
DROP TABLE IF EXISTS characters CASCADE;
DROP TABLE IF EXISTS template_fields CASCADE;
DROP TABLE IF EXISTS templates CASCADE;
DROP TABLE IF EXISTS users CASCADE;

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(254) UNIQUE NOT NULL,
    password TEXT NOT NULL,
    firstname VARCHAR(50) NOT NULL,
    lastname VARCHAR(50) DEFAULT '',
    username VARCHAR(50),
    bio TEXT DEFAULT '',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

CREATE TABLE templates (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT '',
    id_user INTEGER NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE template_fields (
    id SERIAL PRIMARY KEY,
    id_template INTEGER NOT NULL,
    label VARCHAR(100) NOT NULL,
    field_type VARCHAR(50) NOT NULL DEFAULT 'text',
    location VARCHAR(20) NOT NULL DEFAULT 'left',
    order_number INTEGER NOT NULL DEFAULT 0,
    placeholder TEXT DEFAULT '',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_template) REFERENCES templates(id) ON DELETE CASCADE
);

CREATE TABLE characters (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT '',
    image VARCHAR(255) DEFAULT 'default.jpg',
    id_user INTEGER NOT NULL,
    id_template INTEGER,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (id_template) REFERENCES templates(id) ON DELETE SET NULL
);

CREATE TABLE character_field_values (
    id SERIAL PRIMARY KEY,
    id_character INTEGER NOT NULL,
    id_template_field INTEGER NOT NULL,
    value TEXT DEFAULT '',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_character) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (id_template_field) REFERENCES template_fields(id) ON DELETE CASCADE,
    UNIQUE (id_character, id_template_field)
);

CREATE INDEX idx_templates_id_user ON templates(id_user);
CREATE INDEX idx_template_fields_id_template ON template_fields(id_template);
CREATE INDEX idx_characters_id_user ON characters(id_user);
CREATE INDEX idx_characters_id_template ON characters(id_template);
CREATE INDEX idx_character_field_values_id_character ON character_field_values(id_character);

INSERT INTO users (email, password, firstname, lastname, bio)
VALUES (
    'demo@example.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Demo',
    'User',
    'Konto testowe'
);

INSERT INTO templates (name, description, id_user)
VALUES (
    'Fantasy Character',
    'Podstawowy szablon postaci fantasy.',
    1
);

INSERT INTO template_fields (id_template, label, field_type, location, order_number, placeholder)
VALUES
    (1, 'Rasa', 'text', 'left', 0, ''),
    (1, 'Klasa', 'text', 'left', 1, ''),
    (1, 'Charakter', 'textarea', 'right', 0, ''),
    (1, 'Umiejetnosci', 'list', 'right', 1, '');

INSERT INTO characters (name, description, image, id_user, id_template)
VALUES
    ('Alysia Thorne', 'Elfia lowczyni z mrocznych lasow.', 'char1.png', 1, 1),
    ('Kaelen Frost', 'Mag lodu wygnany ze swojej wiezy.', 'char2.png', 1, 1);

INSERT INTO character_field_values (id_character, id_template_field, value)
VALUES
    (1, 1, 'Elf'),
    (1, 2, 'Lowczyni'),
    (1, 3, 'Spokojna, uwazna i nieufna wobec obcych.'),
    (2, 1, 'Czlowiek'),
    (2, 2, 'Mag lodu'),
    (2, 3, 'Ambitny i zdystansowany.');
