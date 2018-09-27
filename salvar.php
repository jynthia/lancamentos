$aula->objetivo = utf8_decode($dados_aula['objetivo']);
            $aula->desenvolvimento = utf8_decode($dados_aula['desenvolvimento']);
            $aula->avaliacao = utf8_decode($dados_aula['avaliacao']);
            $faltas_aluno = $dados_aula["faltas"];
            $mf = $dados_aula['mf_id'];
            $motivos = $dados_aula["motivo_falta_id"];

            if (!$aula_id || $aula_id == "") {

                $aula->createdPlano = date("Y-m-d");
                $aula->createdAt = date('Y-m-d H:i:s');
                $aula->updatedAt = date('Y-m-d H:i:s');
                $aula->cadastradoPor = "Pege";
                $aula_id = DAOFactory::getAulaDAO()->insert($aula);
                ## log ##
                DAOFactory::getAulaDAO()->geraLog('Cadastrou aula: ' . $aula->atividade . ' - ' . $aula_id);

            } else {
                $aula->id = $aula_id;
                $aula->updatedAt = date('Y-m-d H:i:s');
                DAOFactory::getAulaDAO()->update($aula);

                // Caso ja haja aula salva, deleta os aula horarios associados e em seguida a aula disciplina
                $id_para_deletar_aula_horarios = DAOFactory::getAulaDisciplinaDAO()->queryByAulaIdValidos($aula_id);

                    foreach ($id_para_deletar_aula_horarios as $key => $value) {
                        DAOFactory::getAulaHorariosDAO()->deleteByAulaDisciplinaId($value->id);
                    }


                DAOFactory::getAulaDisciplinaDAO()->deleteByAulaId($aula_id);

                ## log ##
                DAOFactory::getAulaDAO()->geraLog('Atualizou aula: ' . $aula->atividade . ' - ' . $aula->id);
            }


            $disciplinas_aula_forma_trabalho = $dados_aula['disciplinasAulaFormaTrabalho'];

            $salvos = array();

            // Analisa a grade de horarios
            foreach ($grade as $key => $value) {

                // Busca os horarios que foram selecionados
                if ($dados_aula["aula$key"] != 0) {

                    // Divide o array com os dados correspondentes
                    $res = explode("-", $dados_aula["aula$key"]);

                    // Carrega o horario correspondente aquela aula
                    $horario_id = (int) $res[3];
                    $horario = DAOFactory::getHorarioDAO()->load($horario_id);

                    $turma_disciplina_id = (int) $res[0];
                    $aula_id_int = (int) $aula_id;

                    // Verifica se ja existe um aula disciplina salvo com aquela disciplina
                    $ids_aula_disciplina = DAOFactory::getAulaDisciplinaDAO()->queryByAulaIdTurmaDisciplinaIdValidos($aula_id_int, $turma_disciplina_id);

                    // Se ja houver aula disciplina para aquela aula e disciplina
                    if(!empty($ids_aula_disciplina)) {

                        // Percorre o array de aulas disciplina salvos em busca do correspondente para obter o id
                        foreach($salvos as $s) {

                            if($s["dis"] == $turma_disciplina_id) {


                                // Cria um aula horario associado aquela aula disciplina
                                $aulaHorario = new AulaHorario();
                                $aulaHorario->horarioId = $horario_id;
                                $aulaHorario->horaInicio = $horario->horaInicio;
                                $aulaHorario->horaFim = $horario->horaFim;
                                $aulaHorario->aulaDisciplinaId = $s['id'];
                                $aulaHorario->createdAt = date('Y-m-d H:i:s');

                                $id_aula_horario = DAOFactory::getAulaHorariosDAO()->insert($aulaHorario);
                            }
                        }

                    }

                    // Cria a aula disciplina para aquela aula caso nao haja outro criado para aquela materia
                    if(empty($ids_aula_disciplina))
                    {
                        $disciplinaAula = new AulaDisciplina();
                        $disciplinaAula->aulaId = $aula_id;
                        if (isset($_SESSION['idprofessor']))
                            $disciplinaAula->servidorId = $_SESSION['idprofessor'];
                        else
                            $disciplinaAula->servidorId = "";
                        $disciplinaAula->turmaDisciplinaId = (int)$res[0];
                        $disciplinaAula->created = date('Y-m-d H:i:s');
                        $disciplinaAula->status = 1;
                        $disciplinaAula->horarioId = "";

                        if (!empty($disciplinas_aula_forma_trabalho[$turma_disciplina_id]))
                            $disciplinaAula->formaTrabalho = $disciplinas_aula_forma_trabalho[$turma_disciplina_id];


                        $id_aula_disciplina = DAOFactory::getAulaDisciplinaDAO()->insert($disciplinaAula);

                        $salvos[$key]['id'] = $id_aula_disciplina;
                        $salvos[$key]["dis"] = (int)$res[0];


                        // Adiciona o objeto aula horario associado aquela aula disciplina
                        $aulaHorario = new AulaHorario();
                        $aulaHorario->horarioId = $horario_id;
                        $aulaHorario->horaInicio = $horario->horaInicio;
                        $aulaHorario->horaFim = $horario->horaFim;
                        $aulaHorario->aulaDisciplinaId = $id_aula_disciplina;
                        $aulaHorario->createdAt = date('Y-m-d H:i:s');

                        $id_aula_horario = DAOFactory::getAulaHorariosDAO()->insert($aulaHorario);
                        if (empty($disciplinas_aula_forma_trabalho[$turma_disciplina_id]))
                            DAOFactory::getAulaDisciplinaDAO()->setaNull($id_aula_disciplina, array('forma_trabalho'));
                    }

                }

            }
