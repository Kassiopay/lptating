import React, { useState, useEffect } from 'react';

function TestInterface() {
  const [testData, setTestData] = useState(null);
  const [currentQuestion, setCurrentQuestion] = useState(0);
  const [selectedAnswer, setSelectedAnswer] = useState(null);
  const [score, setScore] = useState(0);
  const [completed, setCompleted] = useState(false);

  // Load test data from API
  useEffect(() => {
    const fetchTestData = async () => {
      const testId = new URLSearchParams(window.location.search).get('id');
      const response = await fetch(`/api/tests/${testId}`);
      const data = await response.json();
      setTestData(data);
    };
    fetchTestData();
  }, []);

  const handleAnswerSelect = (answerId) => {
    setSelectedAnswer(answerId);
  };

  const handleNextQuestion = () => {
    // Check if answer is correct
    const isCorrect = testData.questions[currentQuestion].answers
      .find(a => a.id === selectedAnswer)?.correct;

    if (isCorrect) {
      setScore(score + 1);
    }

    // Move to next question or complete test
    if (currentQuestion < testData.questions.length - 1) {
      setCurrentQuestion(currentQuestion + 1);
      setSelectedAnswer(null);
    } else {
      setCompleted(true);
      // Save results to backend
      saveTestResults();
    }
  };

  const saveTestResults = async () => {
    await fetch('/api/test-results', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        testId: testData.id,
        score: score,
        totalQuestions: testData.questions.length
      })
    });
  };

  if (!testData) return <div>Loading test...</div>;

  if (completed) {
    return (
      <div className="test-completed">
        <h2>Test Completed</h2>
        <p>Your score: {score}/{testData.questions.length}</p>
        <a href="/testlist" className="return-link">Back to Tests</a>
      </div>
    );
  }

  const currentQ = testData.questions[currentQuestion];

  return (
    <div className="test-container">
      <div className="progress">
        Question {currentQuestion + 1} of {testData.questions.length}
      </div>
      
      <h2>{currentQ.text}</h2>
      
      <div className="answers">
        {currentQ.answers.map(answer => (
          <div 
            key={answer.id}
            className={`answer ${selectedAnswer === answer.id ? 'selected' : ''}`}
            onClick={() => handleAnswerSelect(answer.id)}
          >
            {answer.text}
          </div>
        ))}
      </div>
      
      <button 
        className="next-button"
        disabled={!selectedAnswer}
        onClick={handleNextQuestion}
      >
        {currentQuestion === testData.questions.length - 1 ? 'Finish' : 'Next'}
      </button>
    </div>
  );
}

export default TestInterface;
