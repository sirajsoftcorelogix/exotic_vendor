-- Book language role fields (comma-separated book_languages.id values).
-- `language` remains the readonly combined display / API summary (language names).

ALTER TABLE vp_inbound
    ADD COLUMN original_languages VARCHAR(500) NULL DEFAULT NULL AFTER language,
    ADD COLUMN translation_languages VARCHAR(500) NULL DEFAULT NULL AFTER original_languages,
    ADD COLUMN transliteration_languages VARCHAR(500) NULL DEFAULT NULL AFTER translation_languages,
    ADD COLUMN commentary_languages VARCHAR(500) NULL DEFAULT NULL AFTER transliteration_languages,
    ADD COLUMN word_meaning_languages VARCHAR(500) NULL DEFAULT NULL AFTER commentary_languages,
    ADD COLUMN explanation_languages VARCHAR(500) NULL DEFAULT NULL AFTER word_meaning_languages,
    ADD COLUMN script_languages VARCHAR(500) NULL DEFAULT NULL AFTER explanation_languages;
