up:
	./vendor/bin/sail up -d || docker compose up -d

dev: up
	@echo "🎨 Iniciando Frontend (Vite)..."
	docker compose exec app npm run dev

down:
	./vendor/bin/sail down || docker compose down

setup:
	@echo "Copiando .env..."
	cp .env.example .env || true
	@echo "Construindo containers..."
	docker compose up -d --build
	@echo "Instalando dependências do PHP..."
	docker compose exec app composer install
	@echo "Gerando chave..."
	docker compose exec app php artisan key:generate
	@echo "Instalando dependências do Node..."
	docker compose exec app npm install
	@echo "Aguardando banco de dados..."
	sleep 10
	@echo "Rodando migrations..."
	docker compose exec app php artisan migrate --seed
	@echo "Pronto!"

zsh:
	docker compose exec app zsh