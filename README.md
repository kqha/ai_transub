# AI Translate Subtitle
A simple PHP script to translate subtitle in .srt file using Ollama. This script will translate a .srt file line by line by instructing an AI model running in Ollama server to translate them. The quality of translation will depend on the AI model used. This script is tested on Windows 11, but should run fine on whatever OS that have PHP installed and can run php command from command line, console or terminal.

## Requirements
1. Installed Ollama and at least one model ready to use
> The Ollama server doesn't have to be on the same computer the script is running from. As long as Ollama server can be accessed from the computer this script is running from, it will run just fine.
> By default, Ollama server will only be reachable from the same computer through localhost or 127.0.0.1. If you want to access it from other computer in a network, you must configure Ollama to listen on all address. Google "ollama access from lan", there are many tutorial on how to do so.
2. Installed PHP with minimal version 5

## Usage
1. Either clone this repo or just download ai_transub.php file
2. Open ai_transub.php using whatever text editor you have and adjust the configuration (see Configuration below)
3. From command line or terminal, run ```php ai_transub.php [your srt file]"```, for example: ```php ai_transub.php C:\Subtitles\Satria_Garuda_Bima-X.srt"```
4. Wait for it to finish and your translated subtitle will be saved as 'translated.srt' in your current working directory

## Configuration
Configuration for this script are structured in an array variable named $conf starting from line 3. The configuration keys are:
- url: your Ollama URL consists of host and port (e.g: http://192.168.1.1:11434)
- model: AI model you want to use for translation (e.g: llama3:latest), the list values for this key can be obtained by running "ollama list" from command line or terminal in your Ollama server
- cutoff: number of text to translate limit before flushing (eg: 100). If your subtitle consists of too many lines, Ollama may reject to translate because the message body is too long. ai_transub will flush all previous chat interaction when the number of lines reaches this number. Beware that this behaviour may break context in subsequent translation to prior translations.
- autosave: perform automatic saving every time this number of translations done, so the script can continue from latest autosave if there are obstruction during entire translation
- debug: enable debug (true/false), if enabled every translation lines will be displayed
- tmpdir: temporary directory for autosave file, make sure this directory is writable by user
- sample: At least 4 texts to initiate instruction for Ollama (in English prefereably, as per default language of an AI model)
  - First text is the translation instruction, e.g: ```Translate all next messages to Bahasa Indonesia, retain text formatting in translated text, only returned translated text with formatting only, neved include other text that not included in original message.``` 
  - The second text is the expected reply from AI for the previous instruction, e.g: ```Baik, saya akan menerjemahkan seluruh chat berikutnya ke dalam Bahasa Indonesia dengan tetap mempertahankan format teks yang ada.```
  - Third text is an example of a line of subtitle to translate, e.g: ```Good morning```
  - Fourth text is the expected translation of the third text, e.g: ```Selamat pagi```
