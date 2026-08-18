from flask import Flask, render_template, request, jsonify

app = Flask(__name__)

def key_shift(char):
    if char.isalpha():
        return ord(char.lower()) - ord('a')
    elif char.isdigit():
        return ord(char) - ord('0') + 26
    else:
        return 0

def reverse_encrypt(text, key):
    result = ""
    key_index = 0

    for char in text:
        if char.isalpha():
            shift = key_shift(key[key_index % len(key)])
            base = ord('a') if char.islower() else ord('A')
            new_char = (ord(char) - base + shift) % 26 + base
            result += chr(new_char)
            key_index += 1
        else:
            result += char

    return result[::-1]

def reverse_decrypt(cipher, key):
    cipher = cipher[::-1]
    result = ""
    key_index = 0

    for char in cipher:
        if char.isalpha():
            shift = key_shift(key[key_index % len(key)])
            base = ord('a') if char.islower() else ord('A')
            new_char = (ord(char) - base - shift) % 26 + base
            result += chr(new_char)
            key_index += 1
        else:
            result += char

    return result

def encrypt_message(plaintext1, key, user_key):
    ciphertext = [''] * key
    
    for column in range(key):
        pointer = column
        
        while pointer < len(plaintext1):
            letter = plaintext1[pointer]
            
            if letter.isalpha():
                if letter.isupper():
                    base = ord('A')
                else:
                    base = ord('a')
                
                new = (ord(letter) - base + column + key) % 26 + base
                ciphertext[column] += chr(new)
            else:
                ciphertext[column] += letter
            
            pointer += key
    
    encrypted = ''.join(ciphertext)
    final_encrypted = reverse_encrypt(encrypted, user_key)
    
    return encrypted, final_encrypted

def decrypt_message(cipher, key, user_key):
    cipher = reverse_decrypt(cipher, user_key)
    n = len(cipher)
    num_rows = n // key
    extra = n % key
    
    columns = []
    start = 0
    
    for column in range(key):
        length = num_rows + (1 if column < extra else 0)
        columns.append(cipher[start:start + length])
        start += length
    
    decrypted_columns = [''] * key
    
    for column in range(key):
        shift = column + key
        
        for letter in columns[column]:
            if letter.isalpha():
                if letter.isupper():
                    base = ord('A')
                else:
                    base = ord('a')
                
                new = (ord(letter) - base - shift) % 26 + base
                decrypted_columns[column] += chr(new)
            else:
                decrypted_columns[column] += letter
    
    plaintext = ""
    for i in range(max(len(col) for col in decrypted_columns)):
        for col in decrypted_columns:
            if i < len(col):
                plaintext += col[i]
    
    return plaintext

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/encrypt', methods=['POST'])
def encrypt():
    data = request.json
    plaintext = data.get('plaintext', '')
    key = int(data.get('key', 1))
    user_key = data.get('user_key', '')
    
    if not plaintext or not user_key or key < 1:
        return jsonify({'error': 'Invalid input'}), 400
    
    try:
        intermediate, final = encrypt_message(plaintext, key, user_key)
        return jsonify({
            'intermediate': intermediate,
            'encrypted': final
        })
    except Exception as e:
        return jsonify({'error': str(e)}), 400

@app.route('/decrypt', methods=['POST'])
def decrypt():
    data = request.json
    ciphertext = data.get('ciphertext', '')
    key = int(data.get('key', 1))
    user_key = data.get('user_key', '')
    
    if not ciphertext or not user_key or key < 1:
        return jsonify({'error': 'Invalid input'}), 400
    
    try:
        decrypted = decrypt_message(ciphertext, key, user_key)
        return jsonify({'decrypted': decrypted})
    except Exception as e:
        return jsonify({'error': str(e)}), 400

if __name__ == '__main__':
    app.run(debug=True, host='0.0.0.0', port=5000)