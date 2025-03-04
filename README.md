# AI Translate Subtitle
A simple PHP script to translate subtitle in .srt file using Ollama. This script will translate a .srt file line by line by instructing an AI model running in Ollama server to translate them. The quality of translation will depend on the AI model used.

Requirements:
1. Installed Ollama and at least one model ready to use
2. Installed PHP with minimal version 5

Usage:
1. Either clone this repo or just download ai_transub.php file
2. Open ai_transub.php using whatever text editor you have and adjust the configuration (see Configuration below)
3. From command line or terminal, run "php ai_transub.php [your srt file]", for example: "php ai_transub.php C:\Subtitles\Satria_Garuda_Bima-X.srt"
4. Wait for it to finish and your translated subtitle will be saved as 'translated.srt' in your current working directory

Configuration:
Configuration for this script are structured in an array variable named $conf starting from line 3. The configuration keys are:
- url: your Ollama URL consists of host and port (e.g: http://192.168.1.1:11434)
- model: AI model you want to use for translation (e.g: llama3:latest), the list values for this key can be obtained by running "ollama list" from command line or terminal in your Ollama server
- cutoff: number of text to translate limit before flushing (eg: 100). If your subtitle consists of too many lines, Ollama may reject to translate because the message body is too long. ai_transub will flush all previous chat interaction when the number of lines reaches this number. Beware that this behaviour may break context in subsequent translation to prior translations.
- debug: enable debug (true/false), if enabled every translation lines will be displayed
- sample_instruction: Sentence (in English prefereably, as per default language of an AI model) to initiate instruction for Ollama to tranlate all subsequent texts (e.g: "Translate all next messages to Bahasa Indonesia, retain text formatting in translated text, only returned translated text with formatting only, neved include other text that not included in original message.")
- sample_response: Example of returned text when AI understood the instruction as per sample_instruction value (e.g: "Baik, saya akan menerjemahkan seluruh chat berikutnya ke dalam Bahasa Indonesia dengan tetap mempertahankan format teks yang ada.")
