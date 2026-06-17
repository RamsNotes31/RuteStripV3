#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
RuteStrip RAG Chatbot Engine
=============================
Retrieval-Augmented Generation (RAG) chatbot untuk informasi jalur pendakian.

Pipeline:
    1. User Query → Preprocessing
    2. SBERT Embedding → Cosine Similarity → Top-K Retrieval
    3. Context Augmentation → Prompt Construction
    4. Google Gemini API → Response Generation

Model:
    - Retrieval: paraphrase-multilingual-MiniLM-L12-v2 (SBERT, 384d)
    - Generation: Google Gemini 2.0 Flash
"""

import argparse
import json
import sys
import os
import re
import time
import warnings

warnings.filterwarnings('ignore')

PROJECT_ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
HF_CACHE_DIR = os.path.join(PROJECT_ROOT, 'storage', 'app', 'huggingface')

os.environ['HF_HUB_DISABLE_SYMLINKS_WARNING'] = '1'
os.environ.setdefault('HF_HOME', HF_CACHE_DIR)
os.environ.setdefault('SENTENCE_TRANSFORMERS_HOME', os.path.join(HF_CACHE_DIR, 'sentence-transformers'))
os.environ.setdefault('TRANSFORMERS_CACHE', os.path.join(HF_CACHE_DIR, 'transformers'))

# ==========================================
# TEXT PREPROCESSING (same as processor.py)
# ==========================================

STOPWORDS_ID = {
    'yang', 'dan', 'di', 'ke', 'dari', 'ini', 'itu', 'dengan', 'untuk', 'pada',
    'adalah', 'sebagai', 'dalam', 'juga', 'atau', 'ada', 'oleh', 'akan', 'sudah',
    'saya', 'kami', 'kita', 'mereka', 'dia', 'ia', 'anda', 'tersebut', 'dapat',
    'bisa', 'harus', 'telah', 'lalu', 'kemudian', 'serta', 'maupun', 'saat',
    'ketika', 'bila', 'kalau', 'jika', 'karena', 'agar', 'supaya', 'hingga',
    'sampai', 'antara', 'seperti', 'yaitu', 'yakni', 'bahwa', 'namun', 'tetapi'
}

PRESERVE_WORDS = {
    'tidak', 'bukan', 'jangan', 'belum', 'tanpa',
    'mudah', 'sulit', 'curam', 'landai', 'panjang', 'pendek',
    'tinggi', 'rendah', 'sejuk', 'panas', 'dingin', 'indah', 'bagus',
    'pemula', 'berpengalaman', 'santai', 'menantang', 'ekstrem'
}


def preprocess_text(text: str, remove_stopwords: bool = True) -> str:
    """
    Preprocessing teks:
    1. Data Cleaning
    2. Case Folding
    3. Stopword Removal (selektif)
    """
    if not text:
        return ""
    text = re.sub(r'https?://\S+|www\.\S+', '', text)
    text = re.sub(r'[^\w\s\-]', ' ', text)
    text = re.sub(r'\b\d+\b', '', text)
    text = re.sub(r'\s+', ' ', text).strip()
    text = text.lower()
    if remove_stopwords:
        words = text.split()
        filtered = [w for w in words if w in PRESERVE_WORDS or w not in STOPWORDS_ID]
        text = ' '.join(filtered)
    return text


# ==========================================
# SBERT MODEL
# ==========================================

_model = None

def get_local_model_path():
    model_dir = os.path.join(
        HF_CACHE_DIR,
        'sentence-transformers',
        'models--sentence-transformers--paraphrase-multilingual-MiniLM-L12-v2',
    )
    refs_main = os.path.join(model_dir, 'refs', 'main')
    if os.path.exists(refs_main):
        with open(refs_main, 'r', encoding='utf-8') as ref_file:
            revision = ref_file.read().strip()
        snapshot = os.path.join(model_dir, 'snapshots', revision)
        if os.path.exists(os.path.join(snapshot, 'model.safetensors')):
            return snapshot
    return None

def get_model():
    """Load SBERT model (cached)"""
    global _model
    if _model is None:
        from sentence_transformers import SentenceTransformer
        local_model_path = get_local_model_path()
        model_name = local_model_path or 'sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2'
        _model = SentenceTransformer(model_name)
    return _model


def generate_embedding(text: str) -> list:
    """Generate SBERT embedding vector (384 dimensions)"""
    model = get_model()
    embedding = model.encode(text)
    return embedding.tolist()


def cosine_similarity_single(vec_a, vec_b) -> float:
    """Calculate cosine similarity between two vectors"""
    import numpy as np
    a = np.array(vec_a)
    b = np.array(vec_b)
    dot = np.dot(a, b)
    norm_a = np.linalg.norm(a)
    norm_b = np.linalg.norm(b)
    if norm_a * norm_b == 0:
        return 0.0
    return float(dot / (norm_a * norm_b))


# ==========================================
# RAG PIPELINE
# ==========================================

def retrieve_relevant_routes(query: str, routes_data: list, top_k: int = 5) -> list:
    """
    RETRIEVAL STAGE: Find top-K relevant routes using SBERT + Cosine Similarity
    
    Args:
        query: User's natural language question
        routes_data: List of route dicts with 'embedding', 'name', 'narrative_text', etc.
        top_k: Number of top results to retrieve
    
    Returns:
        List of retrieved routes with similarity scores
    """
    # Preprocess query
    processed_query = preprocess_text(query, remove_stopwords=True)
    
    # Generate query embedding
    query_embedding = generate_embedding(processed_query)
    
    # Calculate similarity with all routes
    scored_routes = []
    for route in routes_data:
        if not route.get('embedding'):
            continue
        
        similarity = cosine_similarity_single(query_embedding, route['embedding'])
        scored_routes.append({
            'id': route.get('id'),
            'name': route.get('name', 'Unknown'),
            'description': route.get('description', ''),
            'narrative_text': route.get('narrative_text', ''),
            'distance_km': route.get('distance_km'),
            'elevation_gain_m': route.get('elevation_gain_m'),
            'naismith_duration_hour': route.get('naismith_duration_hour'),
            'average_grade_pct': route.get('average_grade_pct'),
            'basecamp_name': route.get('basecamp_name', ''),
            'basecamp_address': route.get('basecamp_address', ''),
            'basecamp_lat': route.get('basecamp_lat'),
            'basecamp_lng': route.get('basecamp_lng'),
            'entry_fee': route.get('entry_fee', ''),
            'facilities': route.get('facilities', ''),
            'best_season': route.get('best_season', ''),
            'tips': route.get('tips', ''),
            'similarity_score': round(similarity, 4)
        })
    
    # Sort by similarity (descending) and take top_k
    scored_routes.sort(key=lambda x: x['similarity_score'], reverse=True)
    return scored_routes[:top_k]


def get_weather_info(lat: float, lng: float) -> str:
    """Fetch realtime weather from Open-Meteo API with caching (1 hour)"""
    if not lat or not lng:
        return ""
    try:
        import urllib.request
        import json
        import os
        import time

        cache_file = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), 'storage', 'app', 'weather_cache.json')
        cache_key = f"{lat:.4f}_{lng:.4f}"
        
        # Read Cache
        if os.path.exists(cache_file):
            try:
                with open(cache_file, 'r') as f:
                    cache_data = json.load(f)
                if cache_key in cache_data and (time.time() - cache_data[cache_key]['timestamp'] < 3600):
                    return cache_data[cache_key]['data']
            except json.JSONDecodeError:
                cache_data = {}
        else:
            cache_data = {}

        # Fetch new data
        url = f"https://api.open-meteo.com/v1/forecast?latitude={lat}&longitude={lng}&current=temperature_2m,weather_code,wind_speed_10m&timezone=auto"
        req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
        with urllib.request.urlopen(req, timeout=3) as response:
            api_res = json.loads(response.read())
            curr = api_res.get('current', {})
            temp = curr.get('temperature_2m', 'N/A')
            wind = curr.get('wind_speed_10m', 'N/A')
            code = curr.get('weather_code', 0)
            
            w_desc = "Cerah"
            if code in [1, 2, 3]: w_desc = "Cerah Berawan"
            elif code in [45, 48]: w_desc = "Berkabut"
            elif code in [51, 53, 55, 56, 57]: w_desc = "Gerimis"
            elif code in [61, 63, 65, 66, 67]: w_desc = "Hujan"
            elif code in [71, 73, 75, 77]: w_desc = "Salju"
            elif code in [80, 81, 82]: w_desc = "Hujan Deras"
            elif code >= 95: w_desc = "Badai Petir"
            
            result_str = f"{w_desc}, Suhu: {temp}°C, Angin: {wind} km/h"
            
            # Write to cache
            cache_data[cache_key] = {'data': result_str, 'timestamp': time.time()}
            with open(cache_file, 'w') as f:
                json.dump(cache_data, f)
                
            return result_str
    except Exception as e:
        return ""


def build_augmented_prompt(query: str, retrieved_routes: list, chat_history: list = None) -> str:
    """
    AUGMENTATION STAGE: Construct a prompt with retrieved context
    
    Args:
        query: Original user question
        retrieved_routes: List of retrieved route data
        chat_history: Previous conversation messages
    
    Returns:
        Augmented prompt string for the LLM
    """
    # Build context from retrieved routes
    context_parts = []
    for i, route in enumerate(retrieved_routes, 1):
        parts = [f"[Jalur {i}] {route['name']}"]
        
        if route.get('narrative_text'):
            parts.append(f"  Deskripsi: {route['narrative_text']}")
        if route.get('description'):
            parts.append(f"  Info tambahan: {route['description']}")
        if route.get('distance_km'):
            parts.append(f"  Jarak: {route['distance_km']} km")
        if route.get('elevation_gain_m'):
            parts.append(f"  Kenaikan elevasi: {route['elevation_gain_m']} m")
        if route.get('naismith_duration_hour'):
            parts.append(f"  Estimasi waktu (Naismith): {route['naismith_duration_hour']} jam")
        if route.get('average_grade_pct'):
            parts.append(f"  Grade rata-rata: {route['average_grade_pct']}%")
        if route.get('basecamp_name'):
            parts.append(f"  Basecamp: {route['basecamp_name']}")
        if route.get('basecamp_address'):
            parts.append(f"  Alamat: {route['basecamp_address']}")
        if route.get('entry_fee'):
            parts.append(f"  Tiket masuk: Rp {route['entry_fee']}")
        if route.get('facilities'):
            parts.append(f"  Fasilitas: {route['facilities']}")
        if route.get('best_season'):
            parts.append(f"  Musim terbaik: {route['best_season']}")
        if route.get('tips'):
            parts.append(f"  Tips: {route['tips']}")
            
        # Add realtime weather if coordinates exist
        lat = route.get('basecamp_lat')
        lng = route.get('basecamp_lng')
        if lat and lng:
            weather = get_weather_info(lat, lng)
            if weather:
                parts.append(f"  Cuaca Basecamp Saat Ini (Real-time): {weather}")
                
        parts.append(f"  Skor kemiripan: {route['similarity_score']}")
        
        context_parts.append('\n'.join(parts))
    
    context_text = '\n\n'.join(context_parts)
    
    # Build chat history context
    history_text = ""
    if chat_history:
        history_parts = []
        for msg in chat_history[-6:]:  # Last 6 messages for context
            role = "Pengguna" if msg.get('role') == 'user' else "Asisten"
            history_parts.append(f"{role}: {msg.get('content', '')}")
        history_text = "\n".join(history_parts)
    
    # Construct the full prompt
    system_prompt = """Kamu adalah RuteStrip AI Assistant, chatbot cerdas untuk informasi jalur pendakian gunung di Indonesia.

PERAN:
- Kamu membantu pendaki menemukan jalur pendakian yang sesuai preferensi mereka
- Kamu memberikan informasi detail tentang jalur berdasarkan data yang tersedia
- Kamu memberikan saran keselamatan dan tips pendakian

ATURAN:
1. Jawab HANYA berdasarkan data jalur yang diberikan di bawah (Retrieved Context)
2. Jika informasi tidak tersedia dalam data, katakan dengan jujur bahwa data tersebut belum ada
3. Gunakan Bahasa Indonesia yang natural dan ramah
4. Berikan jawaban yang informatif, terstruktur, dan mudah dipahami
5. Sertakan data numerik (jarak, elevasi, estimasi waktu) jika relevan
6. Jika user bertanya rekomendasi, jelaskan mengapa jalur tersebut cocok
7. Selalu prioritaskan keselamatan pendaki dalam rekomendasi
8. Format jawaban dengan bullet points atau numbering jika memuat banyak info
9. Jangan mengarang informasi yang tidak ada dalam data
10. Jika terdapat informasi Cuaca Basecamp Saat Ini, sebutkan dan berikan peringatan/saran yang sesuai dengan cuaca tersebut"""

    prompt = f"""{system_prompt}

=== RETRIEVED CONTEXT (Data Jalur Pendakian) ===
{context_text}

"""
    
    if history_text:
        prompt += f"""=== RIWAYAT PERCAKAPAN ===
{history_text}

"""
    
    prompt += f"""=== PERTANYAAN PENGGUNA ===
{query}

Berikan jawaban yang informatif dan helpful berdasarkan data jalur di atas:"""
    
    return prompt


def generate_response_gemini(prompt: str, api_key: str, model_name: str = 'gemini-2.0-flash') -> str:
    """
    GENERATION STAGE: Generate response using Google Gemini API
    
    Args:
        prompt: Augmented prompt with context
        api_key: Google Gemini API key
        model_name: Gemini model name (e.g. gemini-2.0-flash, gemini-1.5-flash)
    
    Returns:
        Generated response text
    """
    if not api_key:
        return generate_response_without_gemini(prompt)

    try:
        import google.generativeai as genai
        genai.configure(api_key=api_key)
        model = genai.GenerativeModel(model_name)
        
        response = model.generate_content(
            prompt,
            generation_config=genai.types.GenerationConfig(
                temperature=0.7,
                top_p=0.9,
                top_k=40,
                max_output_tokens=1024,
            )
        )
        
        return response.text
        
    except Exception as e:
        return generate_response_without_gemini(prompt, str(e))


def generate_response_without_gemini(prompt: str, error: str = "") -> str:
    """Fallback response when Gemini is not configured."""
    if "quota" in error.lower() or "429" in error:
        msg = "Maaf, sistem sedang mencapai batas penggunaan API (Quota Limit). Silakan tunggu sebentar sebelum mencoba lagi."
    else:
        msg = f"Gemini belum aktif ({error})." if error else "Gemini belum aktif."
    
    suffix = f"\n\nCatatan sistem: {msg}"
    context_marker = "=== RETRIEVED CONTEXT (Data Jalur Pendakian) ==="
    question_marker = "=== PERTANYAAN PENGGUNA ==="
    context = prompt.split(context_marker, 1)[-1].split(question_marker, 1)[0].strip()

    if not context:
        return "Belum ada konteks jalur yang bisa digunakan untuk menjawab pertanyaan ini." + suffix

    lines = []
    for line in context.splitlines():
        cleaned = line.strip()
        if cleaned.startswith("[Jalur") or cleaned.startswith("Jarak:") or cleaned.startswith("Estimasi waktu") or cleaned.startswith("Grade rata-rata") or cleaned.startswith("Basecamp:") or cleaned.startswith("Skor kemiripan"):
            lines.append(cleaned)

    summary = "\n".join(lines[:30]) or context[:1200]
    return "Berikut jalur paling relevan berdasarkan data yang tersedia:\n\n" + summary + suffix


def rag_chat(query: str, routes_data: list, api_key: str, 
             chat_history: list = None, top_k: int = 5,
             model_name: str = 'gemini-2.0-flash') -> dict:
    """
    Full RAG Pipeline: Retrieve → Augment → Generate
    
    Args:
        query: User's question
        routes_data: All hiking routes with embeddings
        api_key: Gemini API key
        chat_history: Previous messages
        top_k: Number of routes to retrieve
        model_name: Gemini model name
    
    Returns:
        Dict with response, retrieved routes, and timing info
    """
    start_time = time.time()
    
    # Stage 1: RETRIEVAL
    retrieval_start = time.time()
    retrieved_routes = retrieve_relevant_routes(query, routes_data, top_k)
    retrieval_time = round((time.time() - retrieval_start) * 1000, 2)
    
    # Stage 2: AUGMENTATION
    augment_start = time.time()
    augmented_prompt = build_augmented_prompt(query, retrieved_routes, chat_history)
    augment_time = round((time.time() - augment_start) * 1000, 2)
    
    # Stage 3: GENERATION
    generation_start = time.time()
    response_text = generate_response_gemini(augmented_prompt, api_key, model_name)
    generation_time = round((time.time() - generation_start) * 1000, 2)
    
    total_time = round((time.time() - start_time) * 1000, 2)
    
    return {
        'success': True,
        'response': response_text,
        'retrieved_routes': [
            {
                'id': r['id'],
                'name': r['name'],
                'similarity_score': r['similarity_score']
            }
            for r in retrieved_routes
        ],
        'timing': {
            'retrieval_ms': retrieval_time,
            'augmentation_ms': augment_time,
            'generation_ms': generation_time,
            'total_ms': total_time
        },
        'metadata': {
            'model_retrieval': 'paraphrase-multilingual-MiniLM-L12-v2',
            'model_generation': model_name,
            'top_k': top_k,
            'routes_retrieved': len(retrieved_routes)
        }
    }


# ==========================================
# CLI ENTRY POINT
# ==========================================

def main():
    parser = argparse.ArgumentParser(description='RuteStrip RAG Chatbot')
    parser.add_argument('--query', required=True, help='User question')
    parser.add_argument('--data-file', required=True, help='Path to JSON file with routes data')
    parser.add_argument('--api-key', required=True, help='Google Gemini API key')
    parser.add_argument('--model', default='gemini-2.0-flash', help='Gemini model name')
    parser.add_argument('--top-k', type=int, default=5, help='Number of routes to retrieve')
    parser.add_argument('--history-file', help='Path to JSON file with chat history')
    
    args = parser.parse_args()
    
    # Load routes data
    try:
        with open(args.data_file, 'r', encoding='utf-8') as f:
            routes_data = json.load(f)
    except Exception as e:
        print(json.dumps({'success': False, 'error': f'Failed to load routes data: {str(e)}'}))
        sys.exit(1)
    
    # Load chat history if provided
    chat_history = None
    if args.history_file:
        try:
            with open(args.history_file, 'r', encoding='utf-8') as f:
                chat_history = json.load(f)
        except:
            chat_history = None
    
    # Run RAG pipeline
    result = rag_chat(
        query=args.query,
        routes_data=routes_data,
        api_key=args.api_key,
        chat_history=chat_history,
        top_k=args.top_k,
        model_name=args.model
    )
    
    print(json.dumps(result, ensure_ascii=False))


if __name__ == '__main__':
    main()
